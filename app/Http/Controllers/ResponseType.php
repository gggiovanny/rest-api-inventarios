<?php
namespace App\Http\Controllers;

abstract class ResponseType {
    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";
    const WARNING = "WARNING";
    const ERROR = "ERROR";
}