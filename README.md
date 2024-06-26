# Tesis backend - Laravel 11

## Importante:

Para utilizar la libreria excel se debe descomentar la linea q tiene estas 2 extensiones en php.ini de apache:
extension=gd
extension=zip

-   Para ejecutar un job, se necesita poner un worker en ejecucion, en desarrolo se lo hace con _**php artisan queue:work --queue=default**_
-   Para ejecutar un job en produccion se debe instalar un **supervisor**
-   Comando para ejecutar un job desde tinker una vez hecho lo anterior: **\Bus::dispatch(new \App\Jobs\ProcessTesis());**
