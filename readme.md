# �����{���c�[��

[Dillinger](http://dillinger.io/)

# �T�v

����̓`���b�g�̂`�o�h�̎v�z���L�������̂ł���B
�`�i�`�w�Ƃ̒ʐM��z�肵�Ă���B

# �\�z

## ��������@�\

###�Q�l
[ChatWork](http://developer.chatwork.com/ja/)
[jquery�`���b�g](http://studio-key.com/646.html)

* ���[���Ƃ����T�O������
* ���[���̓n�b�V�������ꂽURL�œ������\�ƂȂ�B
* ���[�U�ɂ��΂�ꂽ�n�b�V��������ID�œ������\�ƂȂ�
* ���[�U�̓A�i�E���X���󂯂邱�Ƃ��ł���

###�Z�p�^
[phpLiteAdmin](http://www.hiskip.com/pg-notes/dbtools/phpLiteAdmin.html)

## DDL

#### ���[�����
```
CREATE TABLE rooms (
    /** ���[����� **/
    room_id INTEGER, --���[���h�c
    name STRING NOT NULL, --�쐬�������O���[�v�`���b�g�̃`���b�g��
    description STRING NOT NULL, --�O���[�v�`���b�g�̊T�v�����e�L�X�g
    created_at default CURRENT_TIMESTAMP NOT NULL, --�쐬��
    updated_at default CURRENT_TIMESTAMP NOT NULL, --�X�V��
    PRIMARY KEY(room_id AUTOINCREMENT)
);
```


#### ���[�U���
```
CREATE TABLE users (
    /** ���[�U��� **/
    user_id INTEGER, --���[�U�h�c
    user_hash STRING NOT NULL, --���[�U�n�b�V��
    name STRING NOT NULL, --���[�U��
    room_id INTEGER, --���[���h�c
    begin_message_id INTEGER, --���������ۂ̊J�n���b�Z�[�W�h�c
    user_agent STRING, --���[�U�G�[�W�F���g
    ip_address STRING, --���[�U�̃A�h���X
    port INTEGER, --���[�U�̃|�[�g
    created_at default CURRENT_TIMESTAMP NOT NULL, --�쐬��
    PRIMARY KEY(user_id AUTOINCREMENT)
);
```


#### ���b�Z�[�W���
```
CREATE TABLE messages (
    /** ���b�Z�[�W��� **/
    message_id INTEGER, --���b�Z�[�W�h�c
    user_id INTEGER, --���[�U�h�c
    room_id INTEGER, --���[���h�c
    body STRING NOT NULL, --���b�Z�[�W���e
    type INTEGER default 1, --���b�Z�[�W�̎��(1�E�E�E���b�Z�[�W�A2�E�E�E����)
    created_at default CURRENT_TIMESTAMP NOT NULL, --�쐬��
    PRIMARY KEY(message_id AUTOINCREMENT)
);
```


#### ���Ǐ��
```
CREATE TABLE reads (
    /** ���Ǐ�� **/
    message_id INTEGER, --���b�Z�[�W�h�c
    user_id INTEGER, --���[�U�h�c
    room_id INTEGER, --���[���h�c
    created_at default CURRENT_TIMESTAMP NOT NULL --�쐬��
);
```


## API�̎��

### 3. _GET_ __/rooms__

#### �`���b�g�ꗗ�̎擾

�y���N�G�X�g�z
```
curl -X GET -H "X-ChatToken: �Ǘ��l��API�g�[�N��" "https://api.emeraldchat.com/v1/rooms"
```

�y���X�|���X�z
```
[
  {
    "room_id": 123,
    "name": "Group Chat Name",
    "message_num": 122,
    "last_update_time": 1298905200
  }
]
```


### 4. _POST_ __/rooms__

#### �O���[�v�`���b�g��V�K�쐬

�y���N�G�X�g�z
```
curl -X POST -H "X-ChatToken: �Ǘ��l��API�g�[�N��" -d "description=group+chat+description&name=Website+renewal+project" "https://api.emeraldchat.com/v1/rooms"
```

* description�E�E�E�O���[�v�`���b�g�̊T�v�����e�L�X�g
* name�E�E�E�쐬�������O���[�v�`���b�g�̃`���b�g��

�y���X�|���X�z
```
{
  "room_id": 1234,
  "room_hash": "Y1_5w5GbrFh-vW-g4k_yjy6Hma1yYcoQtaGqhOETdOPtyGpo6Jg2C5YoHyvn6BFhJmLYrsm2N7dRhQcmRbAzbA"
}
```

### 5. _GET_ __/rooms/{room_id}__

#### �`���b�g�̖��O���擾

�y���N�G�X�g�z
```
curl -X GET -H "X-ChatToken: �Ǘ��l��API�g�[�N��" "https://api.emeraldchat.com/v1/rooms/{room_id}"
```

�y���X�|���X�z
```
{
  "name": "Group Chat Name",
  "description": "room description text"
}
```


### 6. _PUT_ __/rooms/{room_id}__

#### �`���b�g�̖��O���A�b�v�f�[�g

�y���N�G�X�g�z
```
curl -X PUT -H "X-ChatToken: �Ǘ��l��API�g�[�N��" -d "description=group+chat+description&name=Website+renewal+project" "https://api.emeraldchat.com/v1/rooms/{room_id}"
```

* description�E�E�E�O���[�v�`���b�g�̊T�v�����e�L�X�g
* name�E�E�E�쐬�������O���[�v�`���b�g�̃`���b�g��

�y���X�|���X�z
```
{
  "room_id": 1234
}
```


### 7. _DELETE_ __/rooms/{room_id}__

#### �O���[�v�`���b�g���폜����

�y���N�G�X�g�z
```
curl -X DELETE -H "X-ChatToken: �Ǘ��l��API�g�[�N��" -d "https://api.emeraldchat.com/v1/rooms/{room_id}"
```

�y���X�|���X�z
```
�Ȃ�
```


### 8. _GET_ __/rooms/{room_hash}/members__

#### �`���b�g�̃����o�[�ꗗ���擾

�y���N�G�X�g�z
```
curl -X GET "https://api.emeraldchat.com/v1/rooms/{room_hash}/members"
```

�y���X�|���X�z
```
[
  {
    "user_id": 123,
    "name": "John Smith"
  }
]
```


### 8.1. _GET_ __/rooms/{room_hash}/members__/{user_hash}

#### �`���b�g�̃����o�[�����擾

�y���N�G�X�g�z
```
curl -X GET "https://api.emeraldchat.com/v1/rooms/{room_hash}/members/{user_hash}"
```

�y���X�|���X�z
```
[
  {
    "name": "John Smith",
    "message_count": 3,
    "begin_message_id": "123",
    "last_create_time": 1298905200
  }
]
```


### 9. _GET_ __/rooms/{room_hash}/members/{user_hash}/messages__

#### �`���b�g�̃��b�Z�[�W�ꗗ���擾�B�O��擾������̍����݂̂�Ԃ��܂��B

�y���N�G�X�g�z
```
curl -X GET "https://api.emeraldchat.com/v1/rooms/rooms/{room_hash}/members/{user_hash}/messages"
```


�y���X�|���X�z
```
[
  {
    "message_id": 1,
    "user":{
        "name": "Test1"
        "who": "self"
    },
    "body": "Test1",
    "type": 2,
    "send_time": "2016-12-13 09:50:50"
  }
]
```


### 10. _POST_ __/rooms/{room_hash}/members/{user_hash}/messages__

#### �`���b�g�ɐV�������b�Z�[�W��ǉ�

�y���N�G�X�g�z
```
curl -X POST -d "body=Hello+EmeraldChat%21" "https://api.emeraldchat.com/v1/rooms/{room_hash}/members/{user_hash}/messages"
```

* body�E�E�E���b�Z�[�W�{��

�y���X�|���X�z
```
{
  "message_id": 1234
}
```


### 11. _GET_ __/rooms/{room_id}/messages/{message_id}__

#### ���b�Z�[�W�����擾

�y���N�G�X�g�z
```
curl -X GET -H "X-ChatToken: �Ǘ��l��API�g�[�N��" "https://api.emeraldchat.com/v1/rooms/{room_id}/messages/{message_id}"
```

�y���X�|���X�z
```
{
  "message_id": 5,
  "user": {
    "name": "Bob",
  },
  "body": "Hello Chatwork!",
  "send_time": 1384242850
}
```


### 12. _POST_ __/rooms/{room_id}/members__

#### �`���b�g�Ƀ��[�U��ǉ�

�y���N�G�X�g�z
```
curl -X POST -d "name=Ryuji" "https://api.emeraldchat.com/v1/rooms/{room_id}/members"
```

* name�E�E�E���[�U��

�y���X�|���X�z
```
{
  "user_hash": 2uhimbRJ6T
}
```


# ���p�t���[

* ���[�����쐬���A�������ꂽ���[��ID(�n�b�V���l)���擾����(API�\�z�ς�)
{room_id:FJOIngow2489u53345lFEklEC}

* ���[���Ƀ��O�C�����܂��B(API�\�z�ς�)
http://chat/rooms/FJOIngow2489u53345lFEklEC

* ���[�U���𑗐M���܂��B(���10����)(API�\�z�ς�)
���X�|���X�F{"user_id": 1234}

* ���b�Z�[�W���擾���܂��B(API�\�z�ς�)
���X�|���X�F[{"message_id": 5,"user": {"name": "Bob"},"body": "Hello Chatwork!","send_time": 1384242850,"update_time": 0}]

* ���b�Z�[�W�𑗐M���܂��B(API�\�z�ς�)
���N�G�X�g�F{"message_id": 1234}

* ����I�Ƀ��b�Z�[�W����M���܂��B(API�\�z�ς�)
���N�G�X�g�F[{"message_id": 5,"user": {"name": "Bob"},"body": "Hello Chatwork!","send_time": 1384242850,"update_time": 0}]

* �ߋ������Q�Ƃ���B(API�\�z�ς�)
���X�|���X�F[{"message_id": 5,"user": {"name": "Bob"},"body": "Hello Chatwork!","send_time": 1384242850,"update_time": 0}]

