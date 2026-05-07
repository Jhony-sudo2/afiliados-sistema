
1. Copiar `.env.example` a `.env`
2. Ajustar credenciales de base de datos y SMTP
3. Ejecutar:

```bash
composer install
```

4. Importar el esquema:

```bash
mysql -u root -p sistema_territorial < database/schema.sql
```

5. Crear un usuario administrador inicial:

```bash
php scripts/create_admin.php admin@example.com "admin_semilla1234" "Administrador General"
```

6. Levantar el proyecto:

```bash
php -S localhost:8000 -t public
```
