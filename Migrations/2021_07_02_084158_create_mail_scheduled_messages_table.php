<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateMailScheduledMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('mail_scheduled_messages', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('account_id');
            $table->text('folder_full_name');
            $table->string('message_uid');
            $table->integer('schedule_timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('mail_scheduled_messages');
    }
}
