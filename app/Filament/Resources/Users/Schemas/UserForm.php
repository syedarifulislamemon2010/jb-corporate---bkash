<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('organization')
                    ->label('Organization')
                    ->relationship('organizationRelation', 'label')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                TextInput::make('mobile_no')
                    ->label('Mobile No')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
//                TextInput::make('password')
//                    ->password()
//                    ->required(fn (string $operation): bool => $operation === 'create')
//                    ->dehydrated(fn ($state): bool => filled($state))
//                    ->maxLength(255),
                Hidden::make('token')->default(generate())
            ]);
    }

    public static function generate()
    {
        $letter = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $number = '123456789';
        $special = '@$%*&';
        $str = '';
        $str .= substr(str_shuffle($letter),0,5);
        $str .= substr(str_shuffle($special),0,1);
        $str .= substr(str_shuffle($number),0,2);
        $str = str_shuffle($str);

        return $str;
    }
}
