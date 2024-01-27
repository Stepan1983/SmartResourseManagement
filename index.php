<?php

class BlockchainPlatform {
    private $usersFile = 'users.json';
    private $users;

    public function __construct() {
        $this->loadUsers();
    }

    public function registerUser($username) {
        // Генерируем случайный закрытый ключ (16 символов)
        $privateKey = $this->generatePrivateKey();

        // Генерируем уникальный открытый ключ
        $publicKey = $this->generatePublicKey($privateKey);

        // Начисляем 100 токенов при регистрации
        $tokens = 100;

        // Регистрируем пользователя
        $this->users[$username] = [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
            'tokens' => $tokens,
        ];

        // Сохраняем зарегистрированного пользователя в файл
        $this->saveUsersToFile();

        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
            'tokens' => $tokens,
        ];
    }

    public function transferTokens($senderUsername, $recipientUsername, $signature, $amount) {
        // Проверяем, что отправитель существует и у него достаточно токенов
    
        if (!isset($this->users[$senderUsername]) || $this->users[$senderUsername]['tokens'] < $amount) {
            return false; // Ошибка: отправитель не существует или у него недостаточно токенов
        }

        // Проверяем подпись отправителя (пароль)
        $senderPublicKey = $this->users[$senderUsername]['publicKey'];

        if ($signature != $senderPublicKey) {
            return false; // Ошибка: неверная подпись
        }

        // Передаем токены
        $this->users[$senderUsername]['tokens'] -= $amount;
        $this->users[$recipientUsername]['tokens'] += $amount;

        // Сохраняем обновленную информацию в файл
        $this->saveUsersToFile();

        return true; // Успех: токены успешно переданы
    }


    private function generatePrivateKey() {
        // Генерация случайного закрытого ключа (16 символов)
        return bin2hex(random_bytes(8)); // 8 байт = 16 символов в шестнадцатеричной системе
    }

    private function generatePublicKey($privateKey) {
        // Пример генерации уникального открытого ключа на основе закрытого ключа
        return hash('sha256', $privateKey);
    }

    private function loadUsers() {
        if (file_exists($this->usersFile)) {
            $this->users = json_decode(file_get_contents($this->usersFile), true);
        } else {
            $this->users = [];
        }
    }

    private function saveUsersToFile() {
        $jsonContent = json_encode($this->users, JSON_PRETTY_PRINT);
        file_put_contents($this->usersFile, $jsonContent);
    }

  ////////// Реализуем дочерний смарт-контракт
  public function createContract($username, $contractName) {
      // Проверяем, что пользователь существует
      if (!isset($this->users[$username])) {
          return false; // Ошибка: пользователь не существует
      }

      // Получаем информацию о пользователе
      $userData = $this->users[$username];

      // Создаем уникальный открытый ключ для дочернего контракта
      $childPublicKey = $this->generatePublicKey($this->generatePrivateKey());

      // Создаем дочерний смарт-контракт
      $childContract = [
          'publicKey' => $childPublicKey,
          'parentPublicKey' => $userData['publicKey'],
          // Дополнительные поля и методы контракта могут быть добавлены здесь
      ];

      // Сохраняем информацию о дочернем контракте в файл или базу данных
      $this->saveContractToFile($contractName, $childContract);

      return true; // Успех: дочерний контракт успешно создан
  }

  private function saveContractToFile($contractName, $contractData) {
    $contractsFile = 'contracts.json';

    if (file_exists($contractsFile)) {
        $contracts = json_decode(file_get_contents($contractsFile), true);
    } else {
        $contracts = [];
    }

    $contracts[$contractName] = $contractData;

    $jsonContent = json_encode($contracts, JSON_PRETTY_PRINT);
    file_put_contents($contractsFile, $jsonContent);
  }
  //////////////
}

// Пример использования
$blockchainPlatform = new BlockchainPlatform();
$userInfo1 = $blockchainPlatform->registerUser('user1');
$userInfo2 = $blockchainPlatform->registerUser('user2');

echo "Initial Balances:\n";
echo "User1 Tokens: " . $userInfo1['tokens'] . "\n";
echo "User2 Tokens: " . $userInfo2['tokens'] . "\n";

// Передача токенов от user1 к user2 (пароль - открытый ключ user1)
$transferResult = $blockchainPlatform->transferTokens('user1', 'user2', $userInfo1['publicKey'], 50);

$transferResult = $blockchainPlatform->transferTokens('user1', 'user2', $userInfo1['publicKey'], 20);


$contractName = 'childContract1';
$createContractResult = $blockchainPlatform->createContract('user1', $contractName);

if ($createContractResult) {
    echo "Child contract created successfully.\n";
} else {
    echo "Failed to create child contract.\n";
}