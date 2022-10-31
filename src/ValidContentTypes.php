<?php 

namespace Hollow3464\GraphMailHandler;

enum ValidContentTypes: string {
    case ZIP = 'application/zip';
    case JSON = 'application/json';
    case PDF = 'application/pdf';
}