from dotenv import load_dotenv
import os
from flask_session import Session
from flask import Flask, render_template, redirect, request, session, jsonify, url_for
from flask_dance.contrib.google import make_google_blueprint, google
from flask_dance.consumer import oauth_authorized
from flask_login import LoginManager, login_user
import mysql.connector
from datetime import datetime
import requests 
import json

app = Flask(__name__)

app.secret_key = "7eZQ38^8eGkR!v@T9pJmSf$Wm"
app.config['SESSION_TYPE'] = 'filesystem'
app.config["SESSION_PERMANENT"] = False
Session(app)

os.environ['OAUTHLIB_INSECURE_TRANSPORT'] = '1'  # Para HTTP local
google_bp = make_google_blueprint(
    client_id="262262777275-kpf98diqdo9dcpnhs7qlorlduir2ih42.apps.googleusercontent.com",
    client_secret="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",
    scope=["https://www.googleapis.com/auth/userinfo.email",
           "https://www.googleapis.com/auth/userinfo.profile",
           "openid"]
)
app.register_blueprint(google_bp, url_prefix="/login")


try:
    mydb = mysql.connector.connect(
        host="localhost",
        user="root",
        password="Rossas31.",
        database="e_commerce"
    )
    print("Conexão bem-sucedida!")
except mysql.connector.Error as err:
    print(f"Erro de conexão: {err}")
    exit()

@app.route('/login', methods=['GET'])
def login():
    return render_template('login.html')


@app.route('/login', methods=['POST'])
def logged():
    # Sistema de login
    user = request.form.get('user')
    pwd = request.form.get('pwd')

    # Ter a certeza que os espaços em branco não causam problemas
    if user == "" or pwd == "":
        return render_template('login.html')
    # Pesquisar na base de dados
    mycursor = mydb.cursor(dictionary=True)
    query = "SELECT * FROM users WHERE username = %s AND password = %s"
    mycursor.execute(query, (user, pwd))    
    rows = mycursor.fetchall()
    # print(rows)
    # print(user, pwd)
    mycursor.close()

    if len(rows) == 1:
        session['user'] = user
        session['time']= datetime.now()
        session['uid'] = str(rows[0]["id"])

    if 'user' in session:
        return redirect('/')
    
    return render_template('login.html', error="Invalid username or password")

@app.route('/')
def index():
    if 'user' in session:
        return render_template('index.html', user=session['user'], uid=session['uid'])
    else:
        return render_template('index.html')


@app.route('/register', methods=['POST', 'GET'])   
def register():
    if request.method == 'POST':
        pwd = request.form.get('pwd')
        confirm = request.form.get('confirm')
        user = request.form.get('user')
        fname = request.form.get('fname')
        lname = request.form.get('lname')
        email = request.form.get('email')

        # Verificar se a senha e a confirmação são iguais
        if pwd != confirm:
            return render_template('register.html', error="Passwords do not match")
        
        # Verificar se o utilizador já existe
        mycursor = mydb.cursor(dictionary=True)
        query = "SELECT * FROM users WHERE username = %s"
        val = (user,)
        mycursor.execute(query, val)
        row = mycursor.fetchall()
        if len(row) > 0:
            mycursor.close()
            return render_template('register.html', error="Username already exists")
        
        # verificar se o email já existe
        query = "SELECT * FROM users WHERE email = %s"
        val = (email,)
        mycursor.execute(query, val)
        row = mycursor.fetchall()
        if len(row) > 0:    
            mycursor.close()
            return render_template('register.html', error="Email already registered")
        
        # Inserir o utilizador na base de dados
        query = "INSERT INTO users (username, fname, lname, email, password) VALUES (%s, %s, %s, %s, %s)"
        val = (user, fname, lname, email, pwd)
        mycursor.execute(query, val)
        mydb.commit()
        mycursor.close()
        
        return render_template('index.html', error="User registered successfully!")
   
    # Se for GET, apenas renderiza o formulário
    return render_template('register.html')


@oauth_authorized.connect_via(google_bp)
def google_logged_in(blueprint, token):
    resp = google.get("/oauth2/v2/userinfo")
    if resp.ok:
        info = resp.json()
        print("Dados do Google:", info)
        with open("google_user_info.json", "w", encoding="utf-8") as f:
            json.dump(info, f, indent=4, ensure_ascii=False)

    # 4. Extrai os dados necessários
    email = info.get("email")
    name = info.get("name", "")
    fname, lname = (name.split(" ", 1) + [""])[:2]

    # 5. Verifica se o utilizador já existe na base de dados
    mycursor = mydb.cursor(dictionary=True)
    query = "SELECT * FROM users WHERE email = %s"
    mycursor.execute(query, (email,))
    row = mycursor.fetchall()

        # 6. Se não existe, insere o utilizador
    if len(row) == 0:
        query = "INSERT INTO users (username, fname, lname, email, password) VALUES (%s, %s, %s, %s, %s)"
        val = (email.split("@")[0], fname, lname, email, "")
        mycursor.execute(query, val)
        mydb.commit()
        new_user_id = mycursor.lastrowid
        session['uid'] = str(new_user_id)
    else:
        session['uid'] = str(row[0]["id"])

    # 7. Define os dados da sessão
    session['user'] = email.split("@")[0]
    session['time'] = datetime.now().isoformat()
    print("Sessão criada - User:", session['user'], "UID:", session['uid'])

    mycursor.close()

    


# @app.route("/login/google/authorized")
# def google_login():
#     print("Google autorizado:", google.authorized)

#     # 1. Verifica se está autenticado com o Google
#     if not google.authorized:
#         return redirect(url_for('google.login'))

#     # 2. Obtém os dados do Google
#     resp = google.get("https://www.googleapis.com/oauth2/v2/userinfo")
#     if not resp.ok:
#         print("Erro ao obter dados do Google:", resp.status_code, resp.text)
#         return redirect('/login')
    
#     print("olaaAAAAAAAAAAAAAAAAa")

#     info = resp.json()
#     print("Dados do Google:", info)

#     # 3. (Opcional) Guarda os dados no ficheiro JSON
#     with open("google_user_info.json", "w", encoding="utf-8") as f:
#         json.dump(info, f, indent=4, ensure_ascii=False)

    # #4. Extrai os dados necessários
    # email = info.get("email")
    # name = info.get("name", "")
    # fname, lname = (name.split(" ", 1) + [""])[:2]

    # # 5. Verifica se o utilizador já existe na base de dados
    # mycursor = mydb.cursor(dictionary=True)
    # query = "SELECT * FROM users WHERE email = %s"
    # mycursor.execute(query, (email,))
    # row = mycursor.fetchall()
#     print("EMAIL:", email, flush=True)
#     print("Utilizadores encontrados:", len(row), flush=True)

#     with open("google_user_info.json", "r", encoding="utf-8") as file:
#         google_data = json.load(file)
#         if google_data.get("email") != email:
#             print("Email do Google não corresponde ao email obtido:", google_data.get("email"), email)

#     # 6. Se não existe, insere o utilizador
#     if len(row) == 0:
#         query = "INSERT INTO users (username, fname, lname, email, password) VALUES (%s, %s, %s, %s, %s)"
#         val = (email.split("@")[0], fname, lname, email, "")
#         mycursor.execute(query, val)
#         mydb.commit()
#         new_user_id = mycursor.lastrowid
#         session['uid'] = str(new_user_id)
#     else:
#         session['uid'] = str(row[0]["id"])

#     # 7. Define os dados da sessão
#     session['user'] = email.split("@")[0]
#     session['time'] = datetime.now().isoformat()
#     print("Sessão criada - User:", session['user'], "UID:", session['uid'])

#     mycursor.close()

#     # 8. Redireciona para o index
#     return redirect('/')
    


@app.route('/logout', methods=['POST'])
def logout():
     session.clear()
     return redirect('/')


if __name__ == "__main__":
    app.run(debug=True)
    


