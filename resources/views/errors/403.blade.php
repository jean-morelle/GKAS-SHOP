@extends('errors.layout')
@section('code', '403')
@section('title', 'Accès refusé')
@section('message', in_array($exception->getMessage(), ['', 'This action is unauthorized.', 'Forbidden'], true) ? "Vous n'avez pas l'autorisation d'accéder à cette page." : $exception->getMessage())
