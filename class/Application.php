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
                    if ((trim($value) == '') || strlen($value) > 64 || preg_match('/\d+/', $value)) {
                        $errors[$name] = 'Имя, обязательное поле, не должно содержать цифр и не быть больше 64 символов';
                    }
                    break;
                case 'phone':
                    if (!preg_match('/^\+\d{1,3}\s??\(\d{2,3}\)\s??\d{3}\-\d{2}\-?\d{2}$/m', $value) || (trim($value)== '')) {
                        $errors[$name] = 'Телефон, обязательное поле, должно быть в правильном международном формате. Например +38 (067) 123-45-67';
                    }
                    break;
                case 'email':
                    if (!trim($value) == '' && !preg_match('/^.+\@.+\..+$/', $value)) {
                        $errors[$name] = 'E-mail должeн быть либо пустым либо содержать валидный адрес e-mail';
                    }
                    break;
                case 'comment':
                    if (!trim($value) == '' && (preg_match('/[\<\>]+/', $value) || strlen($value) > 1024)) {
                        $errors[$name] = 'поле Комментарий не должно содержать тэгов и быть больше 1024 символов';
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
