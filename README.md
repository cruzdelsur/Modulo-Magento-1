# Cruz del Sur - Modulo Magento 1
Modulo para agregar a Cruz del Sur como método de envío en un Magento 1

### Requisitos
  - php extension habilitada: php_soap
  - php extension habilitada: php_openssl
  - php GD (2.0.28 o superior) 
  - (Recomendado) Librería zlib

### Instalación

Borrar el caché que se encuentra en el directorio var/cache y todas las cookies en el dominio de la tienda. Deshabilitar compilación para Magento 1.4+. Este paso elimina muchos potenciales problemas.

1. Hacer backup de los datos
2. Descargar el contenido de este repositorio y extraer
3. Navegar dentro del directorio y seleccionar "app" y "lib". Usando el cliente FTP subir el contenido al root de la tienda
4. Desloguearse
5. Borrar cache nuevamente e ingresar al panel administrador (si ya estaba logueado hay que desloguearse e ingresar nuevamente)

### Importante

Asegurarse que su Magento use las siguientes extensiones
- \app\design\frontend\default\default\template\checkout\onepage\shipping.phtml
- \app\design\frontend\default\default\template\customer\address\edit.phtml
- \app\design\frontend\default\default\template\persistent\checkout\onepage\billing.phtml
- \app\design\frontend\default\default\template\shipping\tracking\popup.phtml
- \app\design\frontend\default\default\template\checkout\onepage\shipping_method/available.phtml

### Configuración

1. Ir a System -> Configuration -> Shipping Methods
2. Expandir la pestaña: "Cruz del Sur" 
3. Completar con su id de cliente, usuario y clave
4. Otras configuraciones:
   - Modo Testing: Poner el módulo en modo de prueba para no generar datos en producción. Si está habilitado el módulo hara todas las transacciones a la API de prueba y quedará todo registrado en otra DB.
   - Volume Attribute: Atributo del producto que contiene el volumen (en cm3) para poder cotizar.
   - Información del Destinatario: Desde donde tomar la info del Destinatario (nombre, apellido, telefono, email). Se podrá recuperar de la Orden o de la Dirección de Entrega.
   - Captura del Numero de Documento: Desde que entidad se tomará el valor del número de documento necesario para realizar el despacho en CDS.
     - Tomar de la Orden (sales_flat_order): Se deberá completar luego el campo "Atributo de la Orden" con el nombre del atributo de la tabla.
     - Tomar de la Dirección de Envio (sales_flat_order_adress): Se deberá completar luego el campo "Atributo Dirección de Envío" con el nombre del atributo de la tabla.
     - Tomar de Atributos Separados (uno para cliente y otro para invitado): Se deberá seleccionar el "Atributo del Cliente". Luego seleccionar de que entidad se tomará el dato de invitado y completar el nombre del atributo de la tabla.
   - Modo de Despacho: Establece en que instancia el módulo generará el despacho en CDS.
     - Cuando se realiza el envío de la orden: Para que el despacho sea generado en CDS se deberá hacer el envío a mano desde el Control de los Pedidos.
     - Cuando finaliza el Checkout: El despacho se generará en CDS apenas termine el Checkout.
     - Depende del estado de la orden: El despacho se generará de acuerdo al estado de la orden. El módulo tomará todas las ordenes que estén en "X" estado, generará los despachos en CDS y luego le cambia el estado a "Y". Se deberán seleccionar ambos estados de una lista. Para que esto funcione deberá correr el cron "cruzdelsur_order_dispatch" que busca las ordenes a despachar.
   - Enviar Email en Envios: Establece si se manda mail al comprador informando el despacho apenas se genera en CDS.
   - Enviar Email en Seguimiento: Establece si se manda mail al comprador informando el despacho cuando este es confirmado por CDS. Los despachos son confirmados (y se le impacta el número de tracking) a través del cron "cruzdelsur_tracking_lookup"
