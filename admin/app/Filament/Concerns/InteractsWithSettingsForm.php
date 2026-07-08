<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

/**
 * 设置类页面共用的表单外壳：内嵌 form() schema + 底部保存按钮。
 * 使用页需实现 form()/save()，可覆写 getSaveButtonLabel() 定制按钮文案。
 */
trait InteractsWithSettingsForm
{
    protected function getSaveButtonLabel(): string
    {
        return '保存设置';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label($this->getSaveButtonLabel())
                                ->submit('save'),
                        ])->alignment(Alignment::Start),
                    ]),
            ]);
    }
}
