<?php

namespace App\QueryBuilders;

use App\Models\Contact;
use App\Models\Email;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ContactQueryBuilder extends Builder
{
    public function search(string $term): self
    {
        // Поиск по телефону и email
        $phoneIds = Phone::where('number', 'like', '%'.$term.'%')->pluck('contact_id')->toArray();
        $emailIds = Email::where('address', 'like', '%'.$term.'%')->pluck('contact_id')->toArray();

        // Поиск по ФИО с использованием существующего индекса (fulltext/BTREE)
        $nameIds = Contact::query()
            ->select('id')
            ->searchByName($term)
            ->pluck('id')
            ->toArray();

        return $this->whereIn('id', array_merge($phoneIds, $emailIds, $nameIds));
    }

    public function searchByName(string $term): self
    {
        $term = trim($term);
        if ($term === '') {
            return $this;
        }

        $words = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY);
        if (! $words) {
            return $this;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // OR-поиск по словам, префиксный с '*', порядок слов не важен
            $booleanQuery = implode(' ', array_map(static function ($w) {
                return addcslashes($w, "'\"\+-><()~*:@").'*';
            }, $words));

            return $this
                ->whereRaw(
                    'MATCH(surname, name, patronymic) AGAINST (? IN BOOLEAN MODE)',
                    [$booleanQuery]
                )
                ->orderByRaw(
                    'MATCH(surname, name, patronymic) AGAINST (? IN BOOLEAN MODE) DESC',
                    [$booleanQuery]
                );
        }

        // Fallback для SQLite/прочих: LIKE по любому слову и любой из колонок
        return $this->where(function ($q) use ($words) {
            foreach ($words as $w) {
                $like = '%'.$w.'%';
                $q->orWhere('surname', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('patronymic', 'like', $like);
            }
        });
    }
}
