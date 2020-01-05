<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdtmrolToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {

        //$this->dropColumn( 'users','claimed', 0 ); 
        
        Schema::table('users', function (Blueprint $table) {
            if(!Schema::hasColumn('users', 'idtmrol')){
                $table->unsignedBigInteger('idtmrol')->index('idtmrol')->default(1)->comment('Por medio de esta llave ligaremos el rol de la empresa');
            }
            if(!Schema::hasColumn('users', 'claimed')){         
                $table->smallInteger('claimed')->default(1)->comment("¿Esta cuenta ya ha sido reclamada 0 = no | 1 = si");
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if(Schema::hasColumn('users', 'idtmrol')){
                $table->foreign('idtmrol')->references('idtmrol')->on('tmrol');
            }
        });
        
    }

    /**
     * Elimina una columna de una tabla
     *
     * @param  [string] name
     * @param  [string] column
     * @return void
     */
     public function dropColumn( $tableName, $column, $isForeign ){
        $object_parameter = new stdClass; 
        $object_parameter->column = $column; 
        $object_parameter->isForeign = $isForeign; 
        // Check for its existence before dropping
        if (Schema::hasColumn($tableName, $column)) {
            if( $object_parameter->isForeign === 1 ){
                Schema::table($tableName, function (Blueprint $table) use( $object_parameter ) {
                    $table->dropForeign([$object_parameter->column]);                
                });
            }
            Schema::table($tableName, function (Blueprint $table) use( $object_parameter ) {
                $table->dropColumn( $object_parameter->column);
            });
        }
     }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        $this->dropColumn( 'users','idtmrol', 1 );        
        $this->dropColumn( 'users','claimed', 0 );        
    }
}
