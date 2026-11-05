<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

class GoogleLinkingException extends RuntimeException
{
    public static function emailUnverified(): self
    {
        return new self('Email Google Anda belum terverifikasi. Verifikasi email terlebih dahulu.');
    }

    public static function accountInactive(): self
    {
        return new self('Akun ini sedang tidak aktif. Hubungi administrator organisasi.');
    }

    public static function emailAlreadyRegisteredUnverified(): self
    {
        return new self('Email ini sudah terdaftar tetapi belum diverifikasi. Verifikasi email terlebih dahulu sebelum masuk dengan Google.');
    }
}
