=== MANUAL DE INSTALACIÓN DEL FBPLUGIN v1.0 ===

Advertencia: este plugin ha sido desarrollado para Moodle 2.5.5+, tanto la instalación como el funcionamiento de dicho plugin
no esta garantizado para una versión de Moodle que no sea la misma.

Para empezar, "fbplugin" se sincroniza con el plugin "mod_feedback" (versión 2013050100). Así pues, lo primero será confirmar que nuestro sitio Moodle disponga de este módulo y que esté habilitado. Es posible hacer dichas comprobaciones navegando a "Administración del sitio > Extensiones > Vista general de extensiones".

Siguiendo los pasos de "Usuarios como clientes con ficha" en "Administración del sitio > Extensiones > Servicios Web > Vista general" o en https://docs.moodle.org/25/en/Using_web_services resulta sencillo activar el uso de Web Services en nuestro sitio Moodle. A continuación se resumen los pasos principales, incluyendo la instalación del plugin:

1º Activación de las Web Services:

En "Administración del sitio > Características avanzadas", habilitamos los servicios web.

2º Activación del protocolo REST

En "Administración del sitio > Extensiones > Servicios Web > Administrar protocolos", habilitamos el protocolo REST.

3º Instalación del Plugin:

Procedemos a la instalación del plugin en nuestro sitio Moodle, lo que añadirá las funciones necesarias para que la app
funcione correctamente y creará un servicio que contenga a las mismas.

Para ello, copiamos el plugin en la carpeta "local" de nuestra instalación de Moodle, que debe quedar de esta manera: "/local/fbplugin".
Al ingresar en la web con permisos de administrador, se disparará un aviso en el que debemos aceptar la instalación del nuevo plugin (actualizar base de datos de Moodle) para que realmente surjan los cambios.

Nota: cada vez que se quieran actualizar los ficheros del plugin en Moodle, no bastará con sustituir dichos ficheros, además de deberá de incrementar el número de versión del plugin que se encuentra en "" y un usuario con permisos de administrador deberá permitir dicho cambio accediendo a la web.

4º Configuración del "shortname" del servicio:

En esta versión de Moodle, no existe la asignación de "shortnames" por web. La inserción de este dato debe de ser manual, manipulando directamente la base de datos. En futuras versiones, es posible que la asignación sea más sencilla: https://tracker.moodle.org/browse/MDL-29807

Para asignar un shortname al nuevo servicio instalado con el plugin, se debe acceder a la tabla "mdl_external_services"
de la base de datos y asignar manualmente un nombre al servicio "Service for fbplugin". La app esta implementada para que funcione con el nombre "wsfbplugin", así que cualquier otro nombre diferente hará que la app no conecte con el plugin instalado.

Nota: modificar la app para cambiar el shortname por defecto es fácil, simplemente hay que modificar la variable global "WS_short_name"
que se encuentra declarada en el archivo "index.html".

5º Habilitando permisos:

Debemos activar dos permisos necesarios para que los usuarios puedan usar el nuevo servicio.

- Permite la creación de calves de seguridad por los usuarios: moodle/webservice:createtoken

- Permite el uso del protocolo REST: webservice/rest:use

Podemos hacer este paso de varias maneras, pero se recomienda añadir estos permisos al rol "Usuario Identificado" para que cualquier persona con cuenta en Moodle, pueda acceder sin errores a la aplicación (disponga o no de encuestas que completar). Otras posibilidades son la de añadir estos permisos a otros roles como el de "Estudiante" o crear un rol propio que añada estos permisos y asignarlo a los usuarios.

Recomendaciones:

Se recomienda que se habilite HTTPS con un certificado válido, para evitar problemas de seguridad.

Problemas:

Para cualquier problema, contactar con Alejandro Molina Salazar (amolinasalazar@gmail.com).

Más información:

https://docs.moodle.org/25/en/Using_web_services
https://docs.moodle.org/dev/Creating_a_web_service_client