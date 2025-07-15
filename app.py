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

app.secret_key = os.getenv('SECRET_KEY', '7eZQ38^8eGkR!v@T9pJmSf$Wm')
app.config['SESSION_TYPE'] = 'filesystem'
app.config["SESSION_PERMANENT"] = False
Session(app)

# Configuração do MySQL
app.config['MYSQL_HOST'] = os.getenv('MYSQL_HOST', 'localhost')
app.config['MYSQL_USER'] = os.getenv('MYSQL_USER', 'root')
app.config['MYSQL_PASSWORD'] = os.getenv('MYSQL_PASSWORD', 'tc231456')
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


@app.route('/search', methods=['GET'])
def search():
    product_name = request.args.get("query")  # melhor usar request.args.get em GET
    with mysql.connection.cursor(MySQLdb.cursors.DictCursor) as cursor:
        cursor.execute("SELECT * FROM products WHERE name = %s", (product_name,))
        row = cursor.fetchone()

    if row:
        return redirect(url_for('produto', product_id=row['id']))
    else:
        error = "Produto inexistente"
        return render_template('index.html', error=error)

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



@app.route('/admin/products/<int:id>', methods=['PUT'])
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





if __name__ == "__main__":
    app.run(debug=True)
