## Плагин "Топ материалов"

Версия: 0.4
Лицензия GNU GPL

***

Выводит топ материалов (по просмотрам) в метку {{ popmat }}

## Установка:

1. Распаковать архив и переместить папку popmat в /plugins.
2. Для файлов /plugins/popmat/config.json и /plugins/popmat/templates/popmat.html выставить права 777.
3. Добавить метку {{ popmat }} в нужном месте шаблона.

## Список изменений:


### 2014-05-06

* Упрощён дефолтный интерфейс и исправлена проверка на существавание метки в шаблоне
* Добавлена метка для вывода значений того параметра, по которому ведётся сортировка материалов

### 2014-03-18

* Переименовал с "топ новостей" в "топ материалов"
* Вывод новостей в шаблонизатор
* Возможность настройки с админки
* Возможость выбора сортировки (Дата, Количество комментариев, просмотров)
* При выборе модуля "Файлы", добавлена возможность выбора сортировки по загрузки