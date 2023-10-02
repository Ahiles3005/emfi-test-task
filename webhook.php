<?php
//Скрипт должен выполнять следующее:
//добавлять текстовое примечание в карточке, по которой был получен хук.
//Если получен хук на создание карточки, то текстовое примечание должно содержать
// название сделки/контакта,
// ответственного и
// время добавления карточки.


//Если получен хук на изменение карточки, то текстовое примечание должно содержать названия и новые значения измененных полей,
// время изменения карточки.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'AmoCrm.php';
$amo = new AmoCrm();
$data = $_POST;

//file_put_contents('log.txt', json_encode($_POST));
if (array_key_exists('leads', $data)) {
    if (array_key_exists('add', $data['leads'])) {
        $lead = $data['leads']['add'][0];
        $leadId = (int) $lead['id'];
        $responsible = $amo->getUserById((int) $lead['responsible_user_id']);
        $notify = 'Название: '.$lead['name'].PHP_EOL;
        $notify .= 'Ответственный: '.$responsible['name'].PHP_EOL;
        $notify .= 'Дата: '.date('c', $lead['updated_at']).PHP_EOL;
        $amo->setNotice($notify, 'leads', $leadId);
        $amo->addToDb($leadId, $lead, 'leads');
    }

    if (array_key_exists('update', $data['leads'])) {
        $lead = $data['leads']['update'][0];
        $leadId = (int) $lead['id'];
        $oldData = $amo->getFromDb($leadId, 'leads');

        $notify = 'Измененные поля: '.PHP_EOL;

        if (array_key_exists('custom_fields', $oldData) && !array_key_exists('custom_fields', $lead)) {
            foreach ($oldData['custom_fields'] as $field) {
                $notify .= "{$field['name']}: Удален".PHP_EOL;
            }
        } elseif (!array_key_exists('custom_fields', $oldData) && array_key_exists('custom_fields', $lead)) {
            foreach ($lead['custom_fields'] as $field) {
                $notify .= "{$field['name']}: {$field['values'][0]['value']}".PHP_EOL;
            }
        } elseif (array_key_exists('custom_fields', $oldData) && array_key_exists('custom_fields', $lead)) {
            $updateId = [];
            foreach ($lead['custom_fields'] as $field) {
                foreach ($oldData['custom_fields'] as $customField) {
                    if ($customField['id'] === $field['id'] && $field['values'][0]['value'] !== $customField['values'][0]['value']) {
                        $updateId[] = $field['id'];
                        $notify .= "{$field['name']}: {$field['values'][0]['value']}".PHP_EOL;
                    }
                }
            }
            foreach ($oldData['custom_fields'] as $field) {
                if (!in_array($field['id'], $updateId)) {
                    $notify .= "{$field['name']}: Удален".PHP_EOL;
                }
            }
        }

        foreach ($oldData as $field => $value) {
            if ($field == 'custom_fields') {
                continue;
            }
            $newValue = $lead[$field] ?? null;
            if ($newValue !== $value) {
                $notify .= "{$field}: $newValue".PHP_EOL;
            }
        }

        $notify .= 'Дата: '.date('c', $lead['updated_at']).PHP_EOL;
        $amo->setNotice($notify, 'leads', $leadId);
        $amo->updateToDb($leadId, $lead, 'leads');
    }
}

if (array_key_exists('contacts', $data)) {
    if (array_key_exists('add', $data['contacts'])) {
        $contact = $data['contacts']['add'][0];
        $contactId = (int) $contact['id'];
        $responsible = $amo->getUserById((int) $contact['responsible_user_id']);
        $notify = 'Название: '.$contact['name'].PHP_EOL;
        $notify .= 'Ответственный: '.$responsible['name'].PHP_EOL;
        $notify .= 'Дата: '.date('c', $contact['updated_at']).PHP_EOL;
        $amo->setNotice($notify, 'contact', $contactId);
        $amo->addToDb($contactId, $contact, 'contact');
    }

    if (array_key_exists('update', $data['contacts'])) {
        $contact = $data['contacts']['update'][0];
        $contactId = (int) $contact['id'];
        $oldData = $amo->getFromDb($contactId, 'contact');

        $notify = 'Измененные поля: '.PHP_EOL;

        if (array_key_exists('custom_fields', $oldData) && !array_key_exists('custom_fields', $contact)) {
            foreach ($oldData['custom_fields'] as $field) {
                $notify .= "{$field['name']}: Удален".PHP_EOL;
            }
        } elseif (!array_key_exists('custom_fields', $oldData) && array_key_exists('custom_fields', $contact)) {
            foreach ($contact['custom_fields'] as $field) {
                $notify .= "{$field['name']}: {$field['values'][0]['value']}".PHP_EOL;
            }
        } elseif (array_key_exists('custom_fields', $oldData) && array_key_exists('custom_fields', $contact)) {
            $updateId = [];
            foreach ($contact['custom_fields'] as $field) {
                foreach ($oldData['custom_fields'] as $customField) {
                    if ($customField['id'] === $field['id'] && $field['values'][0]['value'] !== $customField['values'][0]['value']) {
                        $updateId[] = $field['id'];
                        $notify .= "{$field['name']}: {$field['values'][0]['value']}".PHP_EOL;
                        continue 2;
                    } elseif ($customField['id'] === $field['id'] && $field['values'][0]['value'] === $customField['values'][0]['value']) {
                        $updateId[] = $field['id'];
                        continue 2;
                    }
                }
                $notify .= "{$field['name']}: {$field['values'][0]['value']}".PHP_EOL;
            }
//            var_dump($updateId);
            foreach ($oldData['custom_fields'] as $field) {
                if (!in_array($field['id'], $updateId)) {
                    $notify .= "{$field['name']}: Удален".PHP_EOL;
                }
            }
        }

        foreach ($oldData as $field => $value) {
            if ($field == 'custom_fields') {
                continue;
            }
            $newValue = $contact[$field] ?? null;
            if ($newValue !== $value) {
                $notify .= "{$field}: $newValue".PHP_EOL;
            }
        }
        $notify .= 'Дата: '.date('c', $contact['updated_at']).PHP_EOL;
        $amo->setNotice($notify, 'contacts', $contactId);
        $amo->updateToDb($contactId, $contact, 'contact');
    }
}

