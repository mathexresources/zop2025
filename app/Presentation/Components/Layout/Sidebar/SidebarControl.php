<?php

    declare(strict_types=1);

    namespace App\Presentation\Components\Layout\Sidebar;

    use Nette\Application\UI\Control;

    final class SidebarControl extends Control
    {
        private array $data;

        public function __construct(array $data)
        {
            $this->data = $data;
        }

        public function render(): void
        {
            $this->template->setFile(__DIR__ . '/sidebar.latte');
            $this->template->data = $this->data;
            $this->template->render();
        }
    }