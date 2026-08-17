<?php

declare(strict_types=1);

namespace App\Search;

class SimpleCardSearch
{
    public string $view = 'table';
    public string $sort = 'pc.position';
    public int $page = 1;

    public function __construct(
        public string $q = '',
    ) {
    }

    /**
     * @return array<string,string|int>
     */
    public function toArray(): array
    {
        return [
            'q' => $this->q,
            'view' => $this->view,
            'sort' => $this->sort,
            'page' => $this->page,
        ];
    }
}
