from flask_session import Session
from flask import Flask, render_template, redirect, request, session, jsonify
from simplySQL import SQL
from datetime import datetime
import sqlalchemy.dialects.postgresql


db = SQL (DATABASE_URL)


def logged():
    # Sistema de login
    user = request.form.get('usr').lower()
    pwd = request.form.get('pwd')

    # Ter a certeza que os espaços em branco não causam problemas
    if user == "" or pwd == "":
        return render_template('login.html')
    # Pesquisar na base de dados
    query = "SELECT * FROM users WHERE username = :user AND password = :pwd"
    rows = db.execute(query, usr=user, pwd=pwd)

    if len(rows) == 1:
        session['user'] = user
        session['time']= datetime.now()
        session['uid'] = str(rows[0]["id"])

    if 'user' in session:
        return redirect('/')
    
    return render_template('login.html', error="Invalid username or password")


def register():
    pwd = request.form.get('pwd')
    confirm = request.form.get('confirm')
    user = request.form.get('usr').lower()
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



def logout():
    session.clear()
    return redirect('/')



    


