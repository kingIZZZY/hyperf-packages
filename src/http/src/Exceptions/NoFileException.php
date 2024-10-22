<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Http\Exceptions;

/**
 * Thrown when an UPLOAD_ERR_NO_FILE error occurred with UploadedFile.
 */
class NoFileException extends FileException
{
}