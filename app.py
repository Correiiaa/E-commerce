from dotenv import load_dotenv
import os
from flask_session import Session
from flask import Flask, render_template, redirect, request, session, jsonify
import mysql.connector
from datetime import datetime

app = Flask(__name__)
app.config['SESSION_TYPE'] = 'filesystem'
app.config["SESSION_PERMANENT"] = False
Session(app)

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


@app.route('/login/', methods=['GET','POST'])
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
    print(rows)
    print(user, pwd)
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
    # Página inicial
    if 'user' in session:
        return render_template('index.html', user=session['user'])
    else:
        return render_template('index.html')
    
# def register():
    pwd = request.form.get('pwd')
    confirm = request.form.get('confirm')
    user = request.form.get('user').lower()
    fname = request.form.get('fname')
    lname = request.form.get('lname')
    email = request.form.get('email')

    # Verificar se a senha e a confirmação são iguais
    if pwd != confirm:
        return render_template(register.html, error="Passwords do not match")
    
    # Verificar se o utilizador já existe
    row = db.execute("SELECT * FROM users WHERE username = :user", user=user)
    if len(row) > 0:
        return render_template('register.html', error="Username already exists")
    
    # verificar se o email já existe
    row = db.execute("SELECT * FROM users WHERE email = :email", email=email)
    if len(row) > 0:    
        return render_template('register.html', error="Email already registered")
    
    # Inserir o utilizador na base de dados
    query = "INSERT INTO users (username, password, fname, lname, email) VALUES (:user, :pwd, :fname, :lname, :email)"
    db.execute(query, user=user, pwd=pwd, fname=fname, lname=lname, email=email)



# def logout():
    session.clear()
    return redirect('/')


if __name__ == "__main__":
    app.run(debug=True)
    


