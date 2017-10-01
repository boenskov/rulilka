<?php
/**
 * Этот файл содержит настройки удаленного эталонного сервера для выполнения обновления базы
 * Перед началом работы необходимо указать свои значения настроек и переименовать файл удалив расширение .sample
 */

return [
    # БД с эталоном базы
    "etalon"=>[
        # параметры подключения к базе
        "db"=>[
            'dns' => 'mysql:host=sql;dbname=tendertech;port=3306;charset=UTF8',
            'u' => 'tendertech',
            'p' => 'tendertech',
        ],
        # таблицы из которых не надо копировать данные (обычно это кеши)
        "cache_tables"=>["batch","cache.*","sessions"],
    ],
    # дополнительные пути для поиска бекапов
    "backup_dirs"=>["/init_sql"],
];