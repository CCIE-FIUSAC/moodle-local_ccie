# Web Service CCIE
Plugin moodle para utilizar como Web Service dentro de moodle para uso exclusivo de sistemas de CCIE.

Recurso disponible para clientes autorizados en
`https://elearning.ingenieria.edu.gt/campus/webservice/rest/server.php`

# Funciones
* `hello_world` recurso de prueba.
* `matricular` matricula un usuario a un curso, con estado igual a `active`.
* `desmatricular` un usuario modifica su estado a `suspended`.
* `get_cursos` obtiene el listado de cursos disponibles para matricular.
* `get_authurl` obtiene el enlace para iniciar sesión en modalidad SSO.
* `set_password` Cambia la contraseña de un estudiante.

> NOTA: Moodle debe tener instalado [googleoauth2](https://github.com/CCIE-FIUSAC/moodle-auth_googleoauth2) para utilizar `get_authurl`

# Package

Paso 1. Copiar carpeta de código fuente hacia una carpeta temporal
```bash
cp -r moodle-local_ccie /tmp
mv /tmp/{moodle-local_ccie,ccie}
```

Paso 2. Eliminar ficheros de desarrollo
```bash
cd /tmp
rm -rf ccie/.git ccie/*.sh
```

Paso 3. Crear Zip
```bash
version=2016011401
zip -r local_ccie_moodle28_$version.zip ccie
rm -rf ccie
```
Instalar como plugin regular para Moodle.

# Clientes
* [moodle-local_ccie-wrapper](https://github.com/CCIE-FIUSAC/moodle-local_ccie-wrapper) es la librería de apoyo escrito en PHP.

Cualquier componente de software que permite HTTPS puede utilizar el recurso WS.
