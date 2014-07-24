# Terminal Emulator

This plugin allows for interaction with the unix terminal through the [Codiad](http://www.codiad.com) user interface.

## インストール方法

1. Codiadに管理ユーザー（どのプロジェクトでもアクセス可能なユーザー）としてログインします。
2. マーケットプレイスを起動します。
3. 一番下にURLを指定できる入力エリアが存在しています。そこに、https://github.com/InstantLaravel/Codiad-Jailed-Terminal.gitを指定し、右隣の"Install Manually"ボタンをクリックしてください。

## 改造点

UIを日本語にしました。

プロジェクトルートより上位のディレクトリーに移動できなくしました。

実行可能コマンドをホワイトリストで指定できるようにしました。（ハードコード）

Enterキーだけを叩くと、現在のディレクトリーを表示します。
