<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace StepupReadId\Infrastructure\Logger\Formatter;

use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use function array_map;

/**
 * Formats incoming records into a one-line JSON string. Includes only the channel. level, message, context and extra
 * fields of records, omitting the datetime for example.
 */
class JsonFormatter extends MonologJsonFormatter
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        return parent::format($this->mapRecord($record));
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        return parent::formatBatch(
            array_map(
                function (array $record) {
                    return $this->mapRecord($record);
                },
                $records
            )
        );
    }

    /**
     * @param array<string,string> $record
     *
     * @return array<string,string>
     */
    private function mapRecord(array $record): array
    {
        return [
            'channel' => $record['channel'],
            'level'   => $record['level_name'],
            'message' => $record['message'],
            'context' => $record['context'],
            'extra'   => $record['extra'],
        ];
    }
}
