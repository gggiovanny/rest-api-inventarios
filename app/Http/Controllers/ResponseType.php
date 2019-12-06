<?php
namespace App\Http\Controllers;

abstract class ResponseType {
    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";
    const DELETE = "DELETE";
    const WARNING = "WARNING";
    const ERROR = "ERROR";
    const LOGIN_FAILED = "LOGIN_FAILED";
}