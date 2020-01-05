<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStartupSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toopciones', function (Blueprint $table) {
            $table->bigIncrements('idtoopciones');
            $table->string('qvalue', 250)->comment('valor de la variable');
            $table->string('qname', 250)->comment('Nombre de la variable');
            $table->unsignedBigInteger('idusuario')->index('idusuario')->comment('Por medio de esta llave ligaremos a la tabla users');
            $table->foreign('idusuario')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('tmmensaje', function (Blueprint $table) {
            $table->bigIncrements('idtmmensaje');
            $table->timestamps();
        });

        Schema::create('tdmensaje', function (Blueprint $table) {
            $table->bigIncrements('idtdmensaje');
            $table->unsignedBigInteger('idtmmensaje')->index('idtmmensaje')->comment('Por medio de esta llave ligaremos a la tabla users');
            $table->foreign('idtmmensaje')->references('idtmmensaje')->on('tmmensaje');
            $table->smallInteger('tipo_mensaje')->comment("Aqui dividiremos los mensajes en varios tipo_mensaje");
            $table->timestamps();

            $table->unsignedBigInteger('idusuario_destinatario')->index('idusuario_destinatario')->comment('Por medio de esta llave ligaremos a la tabla users');
            $table->foreign('idusuario_destinatario')->references('id')->on('users');

            $table->unsignedBigInteger('idusuario_emisor')->index('idusuario_emisor')->comment('Por medio de esta llave ligaremos a la tabla users');
            $table->foreign('idusuario_emisor')->references('id')->on('users');
            $table->text('mensaje')->comment('El domicilio fisico de la entidad');
        });

        Schema::create('tmparticipante', function (Blueprint $table) {
            $table->bigIncrements('idtmparticipante');
            $table->timestamps();
        });

        Schema::create('tmstorage', function (Blueprint $table) {
            $table->bigIncrements('idtmstorage');
            $table->timestamps();
        });

        Schema::create('tmlog', function (Blueprint $table) {
            $table->bigIncrements('idtmlog');
            $table->timestamps();
        });

        Schema::create('tmrol', function (Blueprint $table) {
            $table->bigIncrements('idtmrol');
            $table->string('nombre_rol', 250)->comment('Nombre del rol');
            $table->timestamps();
        });

        Schema::create('tdrol', function (Blueprint $table) {
            $table->bigIncrements('idtdrol');
            $table->string('scope', 50)->comment('scope para el token');
            $table->unsignedBigInteger('idtmrol')->index('idtmrol')->comment('Por medio de esta llave ligaremos los permisos de los roles');
            $table->foreign('idtmrol')->references('idtmrol')->on('tmrol');
        });

        Schema::create('tdlog', function (Blueprint $table) {
            $table->bigIncrements('idtdlog');
            $table->unsignedBigInteger('idtmlog')->index('idtmlog')->comment('Por medio de esta llave ligaremos los permisos de los roles');
            $table->foreign('idtmlog')->references('idtmlog')->on('tmlog');            
            $table->text('mensaje')->comment('El domicilio fisico de la entidad');
            $table->dateTime('fecha')->comment('fecha de la generacion del registro'); 
            $table->unsignedBigInteger('idusuario')->index('idusuario')->comment('Por medio de esta llave ligaremos a la tabla users');
            $table->foreign('idusuario')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('tdstorage', function (Blueprint $table) {
            $table->bigIncrements('idtdstorage');
            $table->unsignedBigInteger('idtmstorage')->index('idtmstorage')->comment('Por medio de esta llave ligaremos a todos los participantes de la empresa');
            $table->foreign('idtmstorage')->references('idtmstorage')->on('tmstorage');
            $table->string('url', 250)->comment('url donde guardamos el archivo'); 
            $table->dateTime('fecha')->comment('fecha de la generacion del registro'); 
            $table->unsignedBigInteger('idusuario')->index('idusuario')->comment('Por medio de esta llave ligaremos a la tabla users');
            $table->foreign('idusuario')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tcnotario', function (Blueprint $table) {
            $table->bigIncrements('idtcnotario');
            $table->integer('numero')->comment("numero de clasificacion de notarios");
            $table->string('notario', 250)->comment('Nombre del notario');
            $table->string('calle', 250)->comment('calle del notario');
            $table->string('alcaldia', 100)->comment('alcaldia del notario');
            $table->string('colonia', 250)->comment('colonia del notario');
            $table->string('cp', 10)->comment('cp del notario');
            $table->string('ciudad', 50)->comment('ciudad del notario');
            $table->timestamps();
        });

        Schema::create('tdparticipante', function (Blueprint $table) {
            $table->bigIncrements('idtdparticipante');
            $table->string('cargo', 20)->comment('Cargo que ocupara el usuario en la sociedad');
            $table->date('fecha')->comment('fecha del movimiento');
            $table->date('fecha_inscripcion')->comment('fecha de inscripccion ante el RPPC');
            $table->string('asamblea', 20)->comment('String identificador del nombramiento');
            $table->string('escritura', 20)->comment('String identificador de la escritura donde se registro el movimiento');
            $table->string('RPPC', 20)->comment('String de clasificación RPPC');
            $table->string('folio_mercantil', 20)->comment('String del folio_mercantil');
            $table->timestamps();
            $table->unsignedBigInteger('idtmparticipante')->index('idtmparticipante')->comment('Por medio de esta llave ligaremos a todos los participantes de la empresa');
            $table->foreign('idtmparticipante')->references('idtmparticipante')->on('tmparticipante');

            $table->unsignedBigInteger('idusuario')->index('idusuario')->comment('Por medio de esta llave ligaremos a la tabla users');
            $table->foreign('idusuario')->references('id')->on('users');

            $table->unsignedBigInteger('idtcnotario')->index('idtcnotario')->comment('Por medio de esta llave ligaremos a la tabla tcnotario');
            $table->foreign('idtcnotario')->references('idtcnotario')->on('tcnotario');
        });        

        Schema::create('tmempresa', function (Blueprint $table) {
            $table->bigIncrements('idtmempresa')->comment('The Primary Key for the table.');
            $table->string('identificador', 20)->comment('Label para identificar a la empresa');
            $table->string('denominacion', 250)->comment('Nombre legal de la empresa');
            $table->string('duracion', 20)->comment('String con la duracion de la sociedad');
            $table->string('nacionalidad', 20)->comment('Nacionalidad de la sociedad');
            $table->string('domicilio_social', 20)->comment('¿Estado donde se registro?');
            $table->smallInteger('admite_extranjeros')->comment("¿Esta sociedad admite extranjeros 0 = no | 1 = si");
            $table->text('domicilio_fisico')->comment('El domicilio fisico de la entidad');
            $table->string('telefono', 50)->comment('Telefono de la empresa');
            $table->string('contacto', 250)->comment('Nombre del contacto dentro de la empresa');
            $table->bigInteger('capital_minimo')->comment('Aqui guardaremos centavos 1 peso = 100 centavos');
            $table->bigInteger('valor_nominal')->comment('Aqui guardaremos centavos 1 peso = 100 centavos');
            $table->string('clase_o_serie', 20)->comment('String para clasificar esta entidad');
            $table->bigInteger('acciones')->comment('Aqui guardaremos centavos 1 peso = 100 centavos');
            $table->string('rfc', 20)->comment('El rfc de la entidad legal');
            $table->text('domicilio_fiscal')->comment('El domicilio fiscal de la entidad');
            $table->string('RNIE', 20)->comment('El RNIE es un label clasificatorio de las entidades');
            $table->string('folio', 20)->comment('El folio es un label clasificatorio de las entidades');
            $table->date('fecha_presentacion')->comment('La fecha del registro de la entidad legal');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('idtmparticipante')->index('idtmparticipante')->comment('Por medio de esta llave ligaremos a todos los participantes de la empresa');
            $table->foreign('idtmparticipante')->references('idtmparticipante')->on('tmparticipante');

            $table->unsignedBigInteger('idtmmensaje')->index('idtmmensaje')->comment('Por medio de esta llave ligaremos a todos los mensajes de la empresa');
            $table->foreign('idtmmensaje')->references('idtmmensaje')->on('tmmensaje');
            
            $table->unsignedBigInteger('idtmstorage')->index('idtmstorage')->comment('Por medio de esta llave ligaremos a todos lo archivos de la empresa');
            $table->foreign('idtmstorage')->references('idtmstorage')->on('tmstorage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /*TIP DE EXP +10 en laravel : si no ordenas adecuadamente estos drops mandara un error "foreign key constraint fails"*/
        
        if( Schema::hasTable('toopciones')) {
            Schema::table('toopciones', function (Blueprint $table) {
                $table->dropForeign('toopciones_idusuario_foreign');
            });
        }

        if( Schema::hasTable('tdmensaje')) {
            Schema::table('tdmensaje', function (Blueprint $table) {
                $table->dropForeign('tdmensaje_idtmmensaje_foreign');
                $table->dropForeign('tdmensaje_idusuario_destinatario_foreign');
                $table->dropForeign('tdmensaje_idusuario_emisor_foreign');
            });
        }
        if( Schema::hasTable('tdrol')) {
            Schema::table('tdrol', function (Blueprint $table) {
                $table->dropForeign('tdrol_idtmrol_foreign');
            });
        }
        if( Schema::hasTable('tdlog')) {
            Schema::table('tdlog', function (Blueprint $table) {
                $table->dropForeign('tdlog_idusuario_foreign');
                $table->dropForeign('tdlog_idtmlog_foreign');
            });
        }
        if( Schema::hasTable('tdstorage')) {
            Schema::table('tdstorage', function (Blueprint $table) {
                $table->dropForeign('tdstorage_idtmstorage_foreign');
                $table->dropForeign('tdstorage_idusuario_foreign');
            });
        }
        if( Schema::hasTable('tdparticipante')) {
            Schema::table('tdparticipante', function (Blueprint $table) {
                $table->dropForeign('tdparticipante_idtcnotario_foreign');
                $table->dropForeign('tdparticipante_idtmparticipante_foreign');
                $table->dropForeign('tdparticipante_idusuario_foreign');
            });
        }
        if( Schema::hasTable('tmempresa')) {
            Schema::table('tmempresa', function (Blueprint $table) {
                $table->dropForeign('tmempresa_idtmmensaje_foreign');
                $table->dropForeign('tmempresa_idtmparticipante_foreign');
                $table->dropForeign('tmempresa_idtmstorage_foreign');
            });
        }
        Schema::dropIfExists('toopciones');
        Schema::dropIfExists('tdmensaje');
        Schema::dropIfExists('tdrol');
        Schema::dropIfExists('tdlog');
        Schema::dropIfExists('tdstorage');
        Schema::dropIfExists('tdparticipante');
        Schema::dropIfExists('tcnotario');
        Schema::dropIfExists('tmparticipante');
        Schema::dropIfExists('tmmensaje');
        Schema::dropIfExists('tmstorage');
        Schema::dropIfExists('tmlog');
        Schema::dropIfExists('tmrol');
        Schema::dropIfExists('tmempresa');
    }
}
