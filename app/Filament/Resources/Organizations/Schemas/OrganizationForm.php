<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->required(),
                Select::make('organization_type')
                    ->options([
                        1 => 'MFS',
                        2 => 'Bank',
                        3 => 'Financial Institute',
                        4 => 'Small Business',
                        5 => 'Corporate Client',
                    ])
                    ->required(),
                TextInput::make('mobile_no')
                    ->label('Mobile Number')
                    ->tel()
                    ->required()
                    ->regex('/^01[3-9]\d{8}$/')
                    ->helperText('Example: 01712345678'),
                TextInput::make('address')
                    ->required()
                    ->maxLength(400),
                Select::make('status_id')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])->required(),
                TextInput::make('ip_address')
                    ->rules(['required', 'ip'])
            ]);
    }
}
