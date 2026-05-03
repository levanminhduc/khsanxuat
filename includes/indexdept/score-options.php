<?php

function formatScoreOptionValue($value) {
    $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    return $formatted === '' ? '0' : $formatted;
}

function buildScoreOptions(array $scores) {
    $options = [];
    foreach ($scores as $index => $score) {
        $value = formatScoreOptionValue($score);
        $options[] = [
            'value' => $value,
            'label' => $value,
            'sort_order' => $index + 1
        ];
    }
    return $options;
}

function ensureZeroScoreOption(array $score_options) {
    foreach ($score_options as $option) {
        if (scoreValuesAreEqual($option['value'], 0)) {
            return $score_options;
        }
    }

    array_unshift($score_options, [
        'value' => '0',
        'label' => '0',
        'sort_order' => 0
    ]);

    return $score_options;
}

function scoreOptionsContainZero(array $score_options) {
    foreach ($score_options as $option) {
        if (scoreValuesAreEqual($option['value'], 0)) {
            return true;
        }
    }

    return false;
}

function getLegacyScoreOptions($dept, $thutu) {
    if ($dept === 'kehoach' && ((int) $thutu === 7 || (int) $thutu === 8)) {
        return buildScoreOptions([0, 0.5, 1.5]);
    }

    return buildScoreOptions([0, 1, 3]);
}

function scoreOptionsTableExists($connect) {
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    $result = $connect->query("SHOW TABLES LIKE 'tieuchi_score_options'");
    $exists = ($result && $result->num_rows > 0);

    return $exists;
}

function getConfiguredScoreOptionsMap($connect, $dept) {
    static $cache = [];

    if (isset($cache[$dept])) {
        return $cache[$dept];
    }

    $cache[$dept] = [];

    if (!scoreOptionsTableExists($connect)) {
        return $cache[$dept];
    }

    $sql = "SELECT so.id_tieuchi, so.score_value, so.label, so.sort_order
            FROM tieuchi_score_options so
            JOIN tieuchi_dept tc ON tc.id = so.id_tieuchi
            WHERE tc.dept = ?
              AND so.active = 1
            ORDER BY so.id_tieuchi, so.sort_order, so.score_value";
    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        error_log("Could not prepare score options query: " . $connect->error);
        return $cache[$dept];
    }

    $stmt->bind_param("s", $dept);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $id_tieuchi = (int) $row['id_tieuchi'];
        $value = formatScoreOptionValue($row['score_value']);
        $label = trim((string) ($row['label'] ?? ''));

        if ($label === '') {
            $label = $value;
        }

        if (!isset($cache[$dept][$id_tieuchi])) {
            $cache[$dept][$id_tieuchi] = [];
        }

        $cache[$dept][$id_tieuchi][] = [
            'value' => $value,
            'label' => $label,
            'sort_order' => (int) $row['sort_order']
        ];
    }

    return $cache[$dept];
}

function getScoreOptionsForCriteria($connect, $id_tieuchi, $dept, $thutu) {
    $configured_options = getConfiguredScoreOptionsMap($connect, $dept);
    $id_tieuchi = (int) $id_tieuchi;

    if (!empty($configured_options[$id_tieuchi])) {
        return ensureZeroScoreOption($configured_options[$id_tieuchi]);
    }

    return getLegacyScoreOptions($dept, $thutu);
}

function getDefaultScoreValueForCriteria($connect, $id_tieuchi, $dept, $thutu) {
    $configured_options = getConfiguredScoreOptionsMap($connect, $dept);
    $id_tieuchi = (int) $id_tieuchi;

    if (!empty($configured_options[$id_tieuchi])) {
        if (scoreOptionsContainZero($configured_options[$id_tieuchi])) {
            return '0';
        }

        return $configured_options[$id_tieuchi][0]['value'];
    }

    return '0';
}

function getMaxScoreFromOptions(array $score_options) {
    $max_score = 0;

    foreach ($score_options as $option) {
        $max_score = max($max_score, (float) $option['value']);
    }

    return $max_score;
}

function scoreValuesAreEqual($left, $right) {
    return abs((float) $left - (float) $right) < 0.00001;
}

function isScoreAllowed(array $score_options, $score) {
    if (!is_numeric($score)) {
        return false;
    }

    foreach ($score_options as $option) {
        if (scoreValuesAreEqual($score, $option['value'])) {
            return true;
        }
    }

    return false;
}

function getScoreOptionValuesForMessage(array $score_options) {
    $values = [];

    foreach ($score_options as $option) {
        $values[] = $option['value'];
    }

    return implode(', ', $values);
}
