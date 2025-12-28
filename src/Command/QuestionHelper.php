<?php

namespace Luoyue\WebmanMcp\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use UnitEnum;

/**
 * 问题处理助手类.
 *
 * @template T
 */
final class QuestionHelper
{
    /**
     * 处理问题交互并获取用户输入.
     *
     * @param array<T, array{question: string, regex: string, default?: mixed, validator?: callable, choice?: array|class-string<UnitEnum>}> $questions 问题配置数组
     * @param SymfonyStyle $style Symfony控制台样式
     * @return array<T, string|string[]> 用户回答的答案数组
     */
    public static function handleQuestions(array $questions, SymfonyStyle $style): array
    {
        foreach ($questions as $key => $question) {
            $validator = function ($answer) use ($question, $style) {
                $match = preg_match($question['regex'], $answer ?? $question['default'] ?? '');
                if ($match) {
                    isset($question['validator']) && $match = $question['validator']($answer);
                    return $match ? $answer : null;
                }
                $style->error('Invalid input. Please try again.');
                return null;
            };

            do {
                if ($question['choice'] ?? false) {
                    $choiceList = $question['choice'];
                    $is_enum = false;
                    if (is_string($question['choice']) && is_subclass_of($question['choice'], UnitEnum::class)) {
                        $choiceList = array_map(fn ($case) => $case->value, $question['choice']::cases());
                        $is_enum = true;
                    }
                    $answer = $style->choice(
                        question: $question['question'],
                        choices: $choiceList,
                        default: $question['default'] ?? null,
                        multiSelect: $question['multi_select'] ?? false
                    );
                    $is_enum && $answer = $question['choice'] . '::' . $question['choice']::from($answer)->name;
                } else {
                    $answer = $style->ask($question['question'], $question['default'] ?? null, $validator);
                }
            } while ($answer === null);

            $questions[$key] = $answer;
        }

        return $questions;
    }
}
