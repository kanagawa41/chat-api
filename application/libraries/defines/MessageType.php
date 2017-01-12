<?php
/**
 * ユーザの役割の定数
 */
final class MessageType extends Enum implements DefineImpl{
  const MAKE_ROOM = '1'; //ルーム作成
  const INTO_ROOM = '2'; //入室
}