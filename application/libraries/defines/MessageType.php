<?php
/**
 * ユーザの役割の定数
 */
final class MessageType extends Enum implements DefineImpl{
  const MAKE_ROOM = '1';
  const MESSAGE = '2';
  const INTO_ROOM = '3';
  const DATE = '4';
}