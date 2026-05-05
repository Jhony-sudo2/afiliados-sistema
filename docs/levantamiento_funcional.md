# Levantamiento funcional derivado del Excel

## Hojas analizadas

- `CATALOGO_MUNICIPIOS`
- `INGRESO DE CÁNDIDATO`
- `INGRESO DE AFILIADOS`
- `MENUS Y ROLES`

## Módulos resultantes

### 1. Catálogos
- Departamento
- Municipio
- Comunidad
- Puesto

### 2. Información
- Candidato persona
- Líder comunitario
- Afiliado persona
- Vinculación candidato ↔ puesto
- Vinculación afiliado ↔ comunidad/líder

### 3. Seguridad
- Login
- Contraseñas cifradas
- Verificación de correo
- Recuperación de contraseña
- Roles y alcance territorial

## Roles extraídos

- Administrador
- Delegado Municipal
- Delegado Departamental
- Líder Comunitario

## Campos obligatorios para persona

- Nombres
- Apellidos
- Dirección
- Celular 1
- Fecha de nacimiento
- Profesión u oficio
- DPI

## Reglas de líder comunitario

- requiere departamento
- requiere municipio
- comunidad opcional

## Reglas de afiliado

- departamento obligatorio
- municipio obligatorio
- comunidad opcional
- líder comunitario opcional

## Reglas de vinculación de candidato

### Diputado listado nacional
- casilla obligatoria
- candidato obligatorio

### Diputado parlacen
- casilla obligatoria
- candidato obligatorio

### Diputado departamental distrital
- departamento obligatorio
- candidato obligatorio
- casilla obligatoria

### Alcalde municipal
- departamento obligatorio
- municipio obligatorio
- candidato obligatorio

### Síndico de I a V
- departamento obligatorio
- municipio obligatorio
- candidato obligatorio

### Concejal de I a X
- departamento obligatorio
- municipio obligatorio
- candidato obligatorio

## Decisiones de diseño tomadas

1. Se modeló una tabla común `persons` para evitar duplicidad de datos.
2. Los perfiles `candidate_profiles`, `leader_profiles` y `affiliate_profiles` permiten que una misma persona exista en varios roles funcionales.
3. El alcance territorial por rol quedó configurable desde `.env`.
4. Se añadió auditoría básica y recuperación de contraseña como complemento natural de seguridad.


## Implementación adicional incluida en el proyecto

- Puestos base sembrados automáticamente según las reglas del Excel.
- Menús separados para candidato persona, líder comunitario y afiliado persona usando filtros dedicados sobre el registro unificado.
- Exportación CSV para candidatos, líderes comunitarios y afiliados.
