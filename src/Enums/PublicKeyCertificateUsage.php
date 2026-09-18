<?php

namespace AdamDziuk\LaravelKsef\Enums;

enum PublicKeyCertificateUsage: string
{
    case KsefTokenEncryption = 'KsefTokenEncryption';
    case SymmetricKeyEncryption = 'SymmetricKeyEncryption';
}
