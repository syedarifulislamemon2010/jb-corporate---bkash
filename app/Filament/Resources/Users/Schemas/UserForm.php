<?php

namespace App\Filament\Resources\Users\Schemas;

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
                    ->tel()
                    ->placeholder('01XXXXXXXXX')
                    ->regex('/^01[3-9]\d{8}$/')
                    ->required()
                    ->maxLength(11)
                    ->validationMessages([
                        'regex' => 'Please enter a valid 11-digit Bangladeshi mobile number (e.g. 01712345678).',
                    ]),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->maxLength(255),
            ]);
    }
}
