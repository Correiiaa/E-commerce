from dotenv import load_dotenv
import os
from flask_session import Session
from flask import Flask, render_template, redirect, request, session, jsonify, url_for
from flask_dance.contrib.google import make_google_blueprint, google
from flask_dance.consumer import oauth_authorized
from flask_login import LoginManager, login_user
from flask_mysqldb import MySQL
from datetime import datetime
import requests 
import json
import MySQLdb.cursors

# Carregar variáveis de ambiente se existirem (.env)
load_dotenv()

app = Flask(__name__)

app.secret_key = os.getenv('SECRET_KEY')
app.config['SESSION_TYPE'] = 'filesystem'
app.config["SESSION_PERMANENT"] = False
Session(app)

# Configuração do MySQL
app.config['MYSQL_HOST'] = os.getenv('MYSQL_HOST')
app.config['MYSQL_USER'] = os.getenv('MYSQL_USER')
app.config['MYSQL_PASSWORD'] = os.getenv('MYSQL_PASSWORD')
app.config['MYSQL_DB'] = os.getenv('MYSQL_DB', 'e_commerce')
mysql = MySQL(app)

# Google OAuth
os.environ['OAUTHLIB_INSECURE_TRANSPORT'] = '1'
google_bp = make_google_blueprint(
    client_id=os.getenv('GOOGLE_CLIENT_ID', '262262777275-kpf98diqdo9dcpnhs7qlorlduir2ih42.apps.googleusercontent.com'),
    client_secret=os.getenv('GOOGLE_CLIENT_SECRET', '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'),
    scope=[
        "https://www.googleapis.com/auth/userinfo.email",
        "https://www.googleapis.com/auth/userinfo.profile",
        "openid"
    ]
)
app.register_blueprint(google_bp, url_prefix="/login")

@app.route('/login', methods=['GET'])
def login():
    return render_template('login.html')


@app.route('/login', methods=['POST'])
def logged():
    user = request.form.get('user')
    pwd = request.form.get('pwd')

    if user == "" or pwd == "":
        return render_template('login.html')

    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        query = "SELECT * FROM users WHERE username = %s AND password = %s"
        cursor.execute(query, (user, pwd))
        rows = cursor.fetchall()

    if len(rows) == 1:
        session['user'] = user
        session['time'] = datetime.now().isoformat()
        session['uid'] = str(rows[0]["id"])
        session['role'] = 'admin' if rows[0].get('is_admin', 0) == 1 else 'user'
        return redirect('/')
    
    return render_template('login.html', error="Invalid username or password")


@app.route('/')
def index():
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT id, name, price, image_url FROM products")
        produtos = cursor.fetchall()

    if 'user' in session:
        return render_template('index.html', user=session['user'], uid=session['uid'], produtos=produtos)
    else:
        return render_template('index.html', produtos=produtos)


@app.route('/register', methods=['GET'])
def reges():
    if 'user' in session:
        return render_template('index.html', user=session['user'], uid=session['uid'])
    else:
        return render_template('register.html')

@app.route('/register', methods=['POST', 'GET'])
def register():
    if 'user' in session:
        return render_template('index.html', user=session['user'], uid=session['uid'])
    else:
        if request.method == 'POST':
            pwd = request.form.get('pwd')
            confirm = request.form.get('confirm')
            user = request.form.get('user')
            fname = request.form.get('fname')
            lname = request.form.get('lname')
            email = request.form.get('email')

        if pwd != confirm:
            return render_template('register.html', error="Passwords do not match")

        with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
            # Verificar username
            cursor.execute("SELECT * FROM users WHERE username = %s", (user,))
            if cursor.fetchone():
                return render_template('register.html', error="Username already exists")

            # Verificar email
            cursor.execute("SELECT * FROM users WHERE email = %s", (email,))
            if cursor.fetchone():
                return render_template('register.html', error="Email already registered")

            # Inserir
            query = "INSERT INTO users (username, fname, lname, email, password) VALUES (%s, %s, %s, %s, %s)"
            cursor.execute(query, (user, fname, lname, email, pwd))
            mysql.connection.commit()

        return render_template('index.html', error="User registered successfully!")


@oauth_authorized.connect_via(google_bp)
def google_logged_in(blueprint, token):
    resp = google.get("/oauth2/v2/userinfo")
    if resp.ok:
        info = resp.json()
        print("Dados do Google:", info)
        with open("google_user_info.json", "w", encoding="utf-8") as f:
            json.dump(info, f, indent=4, ensure_ascii=False)

        email = info.get("email")
        name = info.get("name", "")
        fname, lname = (name.split(" ", 1) + [""])[:2]

        with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
            cursor.execute("SELECT * FROM users WHERE email = %s", (email,))
            row = cursor.fetchone()

            if not row:
                query = "INSERT INTO users (username, fname, lname, email, password) VALUES (%s, %s, %s, %s, %s)"
                cursor.execute(query, (email.split("@")[0], fname, lname, email, ""))
                mysql.connection.commit()
                session['uid'] = str(cursor.lastrowid)
            else:
                session['uid'] = str(row["id"])

        session['user'] = email.split("@")[0]
        session['time'] = datetime.now().isoformat()
        print("Sessão criada - User:", session['user'], "UID:", session['uid'])


@app.route('/logout', methods=['POST'])
def logout():
    session.clear()
    return redirect('/')


@app.route('/admin/add_product', methods=['POST'])
def add_product():
    name = request.form.get("name")
    category = request.form.get("category")
    description = request.form.get("description")
    price = request.form.get("price")
    quantity = request.form.get("quantity")
    imagem_url = request.form.get("image")


    try:
        price = float(price)
        quantity = int(quantity)
    except (ValueError, TypeError):
        return "Invalid price or quantity", 400
    
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        query = ("INSERT INTO products (name, category, description, price, quantity, image_url) VALUES (%s, %s, %s, %s, %s, %s)")
        cursor.execute(query, (name, category, description, price, quantity, imagem_url))
        mysql.connection.commit()


    return "Produto adicionado com sucesso"


@app.route('/products', methods=['GET'])
def get_all_products():
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM products WHERE active=1")
        products = cursor.fetchall()
    return jsonify(products)


@app.route('/products/<int:id>', methods=['GET'])
def get_products_by_id(id):
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM products WHERE id=%s", (id,))
        product = cursor.fetchall()
    return jsonify(product)


@app.route('/admin/update-products/<int:id>', methods=['PUT'])
def update_product(id):
    if 'user' not in session or session.get('role') != 'admin':
        return "Unauthorized", 403

    name = request.form.get("name")
    category = request.form.get("category")
    description = request.form.get("description")
    price = request.form.get("price")
    quantity = request.form.get("quantity")
    image_url = request.form.get("image")

    try:
        price = float(price)
        quantity = int(quantity)
    except (ValueError, TypeError):
        return "Invalid price or quantity", 400
    
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        query = "UPDATE products SET name=%s, category=%s, description=%s, price=%s, quantity=%s, image_url=%s WHERE id=%s"
        cursor.execute(query, (name, category, description, price, quantity, image_url, id))
        mysql.connection.commit()

    return "Product updated successfully!"


@app.route('/admin/delete-products/<int:id>' , methods=['PUT'])
def delete_product(id):
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        query = "UPDATE products SET active=0 WHERE id=%s"
        cursor.execute(query,(id,))
        mysql.connection.commit()

    return "Product delete successfully"


@app.route('/search', methods=['GET'])
def search_products():
    search_term = request.form.get("query")

    if not search_term:
        return jsonify([])
    
    search_term = f"%{search_term}%"

    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM products WHERE name LIKE %s OR category LIKE %s", (search_term, search_term))
        products = cursor.fetchall()

    return jsonify(products)


def update_stock(product_id, qty):
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("UPDATE products SET quantity = quanatity - %s WHERE id = %s", (qty, product_id,))
        mysql.connection.commit()

    return "Stock updated succesefully"


@app.route('/low-stock', methods=['GET'])
def get_low_stock():
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM products WHERE quantity < 15")
        products = cursor.fetchall()
    
    return jsonify(products)



@app.route('/cart/add', methods=['POST'])
def add_to_cart():
    if 'user' not in session:
        return jsonify({"error": "Não autenticado"}), 401

    user_id = session['uid']
    product_id = request.form.get('product_id')
    quantity = int(request.form.get('quantity', 1))

    # Verificar se o produto existe
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM products WHERE id = %s", (product_id,))
        product = cursor.fetchone()
        price = product['price']

        if not product:
            return jsonify({"error": "Produto não encontrado"}), 404

        if product['quantity'] < quantity:
            return jsonify({"error": "Estoque insuficiente"}), 400

        # Verificar se o produto já está no carrinho
        cursor.execute(
            "SELECT * FROM cart WHERE user_id = %s AND product_id = %s",
            (user_id, product_id)
        )
        existing = cursor.fetchone()

        if existing:
            # Atualiza a quantidade
            new_quantity = existing['quantity'] + quantity
            new_price = existing['price'] + price
            cursor.execute(
                "UPDATE cart SET quantity = %s, price=%s WHERE user_id = %s AND product_id = %s",
                (new_quantity, new_price, user_id, product_id)
            )
        else:
            # Adiciona novo produto
            cursor.execute(
                "INSERT INTO cart (user_id, product_id, quantity, price) VALUES (%s, %s, %s, %s)",
                (user_id, product_id, quantity, price)
            )

        mysql.connection.commit()

    return jsonify({"message": "Produto adicionado ao carrinho"})


@app.route('/order', methods=['POST'])
def create_order():
    if 'user' not in session:
        return "Não autenticado", 401
    
    user_id = session['uid']
    shipping_address = request.form.get("shipping_address")

    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM cart WHERE user_id = %s", (user_id,))
        orders = cursor.fetchall()

        if not orders:
            return "Carinho vazio", 400

        final_price = 0
        for price in orders:
            final_price += price['price']

        cursor.execute("INSERT INTO orders (user_id, status, total_price, shipping_address) VALUES (%s, %s, %s, %s)",
                       (user_id, "pendente", final_price, shipping_address,))
        
        order_id = cursor.lastrowid

        for item in orders:
            cursor.execute("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (%s, %s, %s, %s)",
                           (order_id, item['product_id'], item['quantity'], item['price'],))
            

            cursor.execute("DELETE FROM cart WHERE user_id = %s", (user_id,))

            mysql.connection.commit()
        
            
        return jsonify({"message": "Encomenda criada com sucesso", "order_id": order_id})
    
    
def get_orders_by_user(user_id):
    if 'user' not in session or session.get('role') != 'admin':
        return "Unauthorized", 403
    
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM orders WHERE user_id = %s", (user_id))
        orders = cursor.fetchall()

    return jsonify(orders)



@app.route('/admin/get_all_orders', methods=['GET'])
def get_all_orders():
    if 'user' not in session or session.get('role') != 'admin':
        return "Unauthorized", 403
    
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM orders")
        orders = cursor.fetchall()

    return jsonify(orders)


if __name__ == "__main__":
    app.run(debug=True)
