<?php
/**
 * ユーザの役割の定数
 */
final class UserRole extends Enum implements DefineImpl{
  const ADMIN = '1';
  const SPECIFIC_USER = '2';
  const ANONYMOUS_USER = '3';
}