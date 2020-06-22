<?php

class Application extends Config
{

    private $routingRules = [
        'Application' => [
            'index' => 'Application/actionIndex'
        ],
        'robots.txt' => [
            'index' => 'Application/actionRobots'
        ],
        'debug' => [
            'index' => 'Application/actionDebug'
        ]
    ];

    /**
     * @var $view View
     */
    private $view;

    function __construct()
    {
        parent::__construct();
        $this->view = new View($this);
        if ($this->requestMethod == 'POST') {
            header('Content-Type: application/json');
            die(json_encode($this->ajaxHandler($_POST)));
        } else {
            //Normal GET request. Nothing to do yet
        }
    }

    public function run()
    {
        if (array_key_exists($this->routing->controller, $this->routingRules)) {
            if (array_key_exists($this->routing->action, $this->routingRules[$this->routing->controller])) {
                list($controller, $action) = explode(DIRECTORY_SEPARATOR, $this->routingRules[$this->routing->controller][$this->routing->action]);
                call_user_func([$controller, $action]);
            } else {
                http_response_code(404);
                die('action not found');
            }
        } else {
            http_response_code(404);
            die('controller not found');
        }
    }

    public function actionIndex()
    {
        return $this->view->render('index');
    }

    public function actionDebug()
    {
        return $this->view->render('debug');
    }

    public function actionRobots()
    {
        return implode(PHP_EOL, ['User-Agent: *', 'Disallow: /']);
    }

    /**
     * Здесь нужно реализовать механизм валидации данных формы
     * @param $data array
     * $data - массив пар ключ-значение, генерируемое JavaScript функцией serializeArray()
     * name - Имя, обязательное поле, не должно содержать цифр и не быть больше 64 символов
     * phone - Телефон, обязательное поле, должно быть в правильном международном формате. Например +38 (067) 123-45-67
     * email - E-mail, необязательное поле, но должно быть либо пустым либо содержать валидный адрес e-mail
     * comment - необязательное поле, но не должно содержать тэгов и быть больше 1024 символов
     *
     * @return array
     * Возвращаем массив с обязательными полями:
     * result => true, если данные валидны, и false если есть хотя бы одна ошибка.
     * error => ассоциативный массив с найдеными ошибками,
     * где ключ - name поля формы, а значение - текст ошибки (напр. ['phone' => 'Некорректный номер']).
     * в случае отсутствия ошибок, возвращать следует пустой массив
     */
    public function actionFormSubmit($data)
    {
        $errors = [];                                  //Отсутствие ошибок
        foreach ($data as $item) {
            $name = $item['name'];
            $value = $item['value'];
            switch ($name) {
                case 'name':
                    if (strlen(trim($value)) == 0) {
                        $errors[$name] = 'Имя - обязательное поле';
                    }
                    if (sizeof(array_intersect(range(0, 9), str_split($value))) > 0) {
                        $errors[$name] = 'Поле Имя не должно содержать цифр';
                    }
                    if (strlen($value) > 64) {
                        $errors[$name] = 'Поле Имя не должно быть больше 64 символов';
                    }
                    break;
                case 'phone':
                    $value = strlen(filter_var($value, FILTER_SANITIZE_NUMBER_INT));
                    if ($value > 16 || $value < 10) {
                        $errors[$name] = 'Поле Телефон должно быть в правильном международном формате. Например +38 (067) 123-45-67';
                    }
                    if (strlen(trim($value)) == 0) {
                        $errors[$name] = 'Телефон - обязательное поле';
                    }
                    break;
                case 'email':
                    if (strlen(trim($value)) != 0 && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$name] = 'E-mail должeн содержать валидный адрес e-mail';
                    }
                    break;
                case 'comment':
                    if (strlen(trim($value)) != 0 && $value != strip_tags($value)) {
                        $errors[$name] = 'поле Комментарий не должно содержать тэгов';
                    }
                    if (strlen($value) > 1024) {
                        $errors[$name] = 'поле Комментарий не должно быть больше 1024 символов';
                    }
                    break;
            }
        }

        return ['result' => count($errors) === 0, 'error' => $errors];
    }


    /**
     * Функция обработки AJAX запросов
     * @param $post
     * @return array
     */
    private function ajaxHandler($post)
    {
        if (count($post)) {
            if (isset($post['method'])) {
                switch ($post['method']) {
                    case 'formSubmit':
                        $result = $this->actionFormSubmit($post['data']);
                        break;
                    default:
                        $result = ['error' => 'Unknown method'];
                        break;
                }
            } else {
                $result = ['error' => 'Unspecified method!'];
            }
        } else {
            $result = ['error' => 'Empty request!'];
        }
        return $result;
    }
}
