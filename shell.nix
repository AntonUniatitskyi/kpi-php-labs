{ pkgs ? import <nixpkgs> {} }:

pkgs.mkShell {
  name = "php_Labs";
  buildInputs = [
    pkgs.php82
    pkgs.php82Packages.composer
  ];
}
