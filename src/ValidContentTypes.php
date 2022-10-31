<?php 

namespace Hollow3464\GraphMailHandler;

enum ValidContentTypes: string {
    case JSON = 'application/json';
    case PDF = 'application/pdf';
    case ZIP = 'application/zip';
}