# Настройка Nginx для GamePay в OSPanel

## Проблема: 403 Forbidden

Если вы видите ошибку 403 Forbidden, это означает, что nginx не может получить доступ к файлам или неправильно настроен document root.

## Решение

### 1. Проверьте структуру проекта

Убедитесь, что структура такая:
```
C:\OSPanel\home\gamepay\public\
├── public\
│   ├── index.php  ← Должен быть здесь!
│   ├── .htaccess
│   └── images\
├── app\
├── bootstrap\
├── config\
└── ...
```

### 2. Настройка виртуального хоста в OSPanel

1. Откройте OSPanel
2. Перейдите в **Настройки → Домены**
3. Добавьте новый домен: `gamepay.e-qarz.uz`
4. Укажите путь к проекту: `C:\OSPanel\home\gamepay\public\public`
   - ⚠️ **Важно**: Укажите именно `public\public`, так как Laravel использует `public` как document root

### 3. Альтернативное решение (если структура другая)

Если ваш проект находится в `C:\OSPanel\home\gamepay\public\`, то:

1. В настройках домена укажите: `C:\OSPanel\home\gamepay\public`
2. Но тогда нужно настроить nginx так, чтобы он указывал на поддиректорию `public`

### 4. Ручная настройка nginx конфига

Найдите конфиг nginx для вашего домена (обычно в `C:\OSPanel\domains\gamepay.e-qarz.uz\` или в `C:\OSPanel\userdata\config\nginx\`)

Создайте или отредактируйте конфиг:

```nginx
server {
    listen 80;
    server_name gamepay.e-qarz.uz;
    
    root C:/OSPanel/home/gamepay/public/public;
    index index.php index.html;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Проверьте права доступа

Убедитесь, что:
- Файлы доступны для чтения
- Директории доступны для чтения и выполнения
- `public/index.php` существует и доступен

### 6. Перезапустите nginx

В OSPanel:
1. Остановите nginx
2. Запустите nginx снова
3. Очистите кеш браузера

### 7. Проверьте логи

Проверьте логи nginx для деталей ошибки:
- `C:\OSPanel\logs\nginx\error.log`

## Быстрая проверка

Выполните в PowerShell:

```powershell
# Проверьте, существует ли index.php
Test-Path "C:\OSPanel\home\gamepay\public\public\index.php"

# Проверьте права доступа
Get-Acl "C:\OSPanel\home\gamepay\public\public" | Format-List
```

## Если ничего не помогает

1. Попробуйте открыть напрямую: `http://gamepay.e-qarz.uz/index.php`
2. Проверьте, что PHP-FPM запущен в OSPanel
3. Убедитесь, что порт 80 не занят другим приложением

