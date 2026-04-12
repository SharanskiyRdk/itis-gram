# Страница входа/регистрации

Изменить header: поменять цвета надписи ItisGram и самого футера, а также убрать кнопку выхода, поскольку пользователя еще нет.
Лучше создать header кастомный с описанием ранее.

Изменить форму регистрации на такой:

<body>
  <div class="login">
    <div class="login-screen">
      <div class="app-title">
        <h1>Login</h1>
      </div>
      <div class="login-form">
        <div class="control-group">
        <input type="text" class="login-field" value="" placeholder="username" id="login-name">
        <label class="login-field-icon fui-user" for="login-name"></label>
        </div>
        <div class="control-group">
        <input type="password" class="login-field" value="" placeholder="password" id="login-pass">
        <label class="login-field-icon fui-lock" for="login-pass"></label>
        </div>
        <a class="btn btn-primary btn-large btn-block" href="#">login</a>
        <a class="login-link" href="#">Lost your password?</a>
      </div>
    </div>
  </div>
</body>

* {
box-sizing: border-box;
}

*:focus {
outline: none;
}
body {
font-family: Arial;
background-color: #3498DB;
padding: 50px;
}
.login {
margin: 20px auto;
width: 300px;
}
.login-screen {
background-color: #FFF;
padding: 20px;
border-radius: 5px
}

.app-title {
text-align: center;
color: #777;
}

.login-form {
text-align: center;
}
.control-group {
margin-bottom: 10px;
}

input {
text-align: center;
background-color: #ECF0F1;
border: 2px solid transparent;
border-radius: 3px;
font-size: 16px;
font-weight: 200;
padding: 10px 0;
width: 250px;
transition: border .5s;
}

input:focus {
border: 2px solid #3498DB;
box-shadow: none;
}

.btn {
border: 2px solid transparent;
background: #3498DB;
color: #ffffff;
font-size: 16px;
line-height: 25px;
padding: 10px 0;
text-decoration: none;
text-shadow: none;
border-radius: 3px;
box-shadow: none;
transition: 0.25s;
display: block;
width: 250px;
margin: 0 auto;
}

.btn:hover {
background-color: #2980B9;
}

.login-link {
font-size: 12px;
color: #444;
display: block;
margin-top: 12px;
}

## Главная страница

Отображение чатов нужно убрать влево, как в телеграмме или яндекс мессенджере. Но в самой левой части нужно оставить небольшой участок как в телеграме
где есть три палочки - нажатие которого показывает фотографию юзера и его имя + если он подтвержденный студент итиса (подтверждает админ), но идет подпись номер группы.
Далее идет кнопка перехода в профиль, создание чата, друзья, избранное и настройки, где под каждое выходит свое неболььшое окно, которое оставляет на себя фокус, затемняя главную страницу сзади.
Ячейку ввода поиска чата оставить но уменьшить над списком чатов и оставить кнопку добавления нового чата: при нажатии на нее выходит маленькое окно
появление которого немного затемняет главный экран, оставив фокус на этом окошке. 
В этом окошке в самом верху будет текст "Начать чат", далее на выбор две кнопки новый чат или пригласить в ItisGram. Далее будет ячейка для поиска друга, а затем будет просто перечисление всех друзей (в том числе и одногруппники обязательно).

Правая часть должна остаться пустой (как в телеграмме) и надпись "Выберите, кому бы хотели написать". При выборе чата он отобразиться в правой части главной страницы. 
Размером списка чатов и отображение самого чата можно управлять мышкой как в самом телеграмме.

<div class="input-wrapper">
                <textarea id="message-input" placeholder="Сообщение..." rows="1" maxlength="4000"></textarea>
                <button class="emoji-btn" id="emoji-btn" title="Эмодзи">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                    </svg>
                </button>
            </div>
            <button class="send-btn" id="send-btn" title="Отправить">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>

Блок html сверху нужно сделать полностью под стилистику телеграмма. С эмодзи пока не знаю что делать, может есть api для этого.

И и как уже понятно, на главной странице убрать Header/footer вообще

# Профиль

Нужно как то его подрегулировать, чтобы отображалось в нем вся нужная инфа пользователя. Также на этой странце должна быь кнопка для перехода в настройки 
и связь с поддержкой.
Также нужно исправить баг со сменой аватара: при добавлении фото выскакивает ошибка, а при новом get запросе аватарка с новым фото.

# Бизнес логика

- Добавление файла: png, jpg, mp3, mp4 (и их отображение), doc/docx, pdf и другие стандартные. Отображение сделать простое, похожее на телеграмм.
- Настройки: при ее вызове на главной странице или в профиле выходит окно, где вверху минимальная инфа об пользователе и его фото, ниже идет кнопка переход на другое окно редактирование профиля, ниже уведомления и звуки, конфидециальность (важная часть), где нужно только ограничение на видимость информации об пользователе: но имя могут видеть все пользователи, номер группы могут видеть только студенты итис. Также кнопка для задания вопроса (как в тг) и смена языка (en/rus)
- i18n: сделать поддержку двух языков (en/rus).
- Шифрование данных?