<?php

namespace AdamDziuk\LaravelKsef\Enums;

enum KsefEnvironment: string
{
    case Test = 'test';
    case Demo = 'demo';
    case Production = 'production';
}
