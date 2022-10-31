<?php 
namespace Hollow3464\GraphMailHandler;

enum AuthStrategy: string {
    case AUTHORIZATION_CODE = 'authorization_code';
    case CLIENT_CREDENTIALS = 'client_credentials';
}