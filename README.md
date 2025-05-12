# 🕹️ El Cartucho - E-commerce Retro - Panel de Administración

## Materia: Aplicaciones Web  
**Grupo 1:**  
- Agustín Bowen  
- Víctor Cretton  

---

## 📦 Proyecto: Panel de Administración de E-commerce Retro

**El Cartucho** es una plataforma de administración de un e-commerce retro-gamer, desarrollada como trabajo práctico para la cátedra de Aplicaciones Web. Este panel está diseñado para gestionar el catálogo de productos, usuarios, pedidos y demás aspectos administrativos de la tienda **El Cartucho**.

---

## 🧠 Sobre la empresa ficticia

🎮 **El Cartucho – Donde la nostalgia se conecta**  
Bienvenidos a *El Cartucho*, tu base retro definitiva.  
Somos más que una tienda: somos un portal al pasado, un rincón para quienes crecieron con un joystick en la mano, soplando cartuchos y soñando en 8 bits.

En *El Cartucho* vas a encontrar:

- 🎮 Consolas clásicas (NES, SNES, Sega Genesis, PS1 y más)  
- 💾 Cartuchos y juegos originales  
- 🕹️ Arcades restaurados  
- 📼 Merchandising retro: remeras, posters, figuras y vinilos  
- 🧠 Ediciones de colección y rarezas para verdaderos gamers old school  

Este panel de administración se enfoca en gestionar todos los aspectos de *El Cartucho*, permitiendo a los administradores controlar el inventario, procesar pedidos, gestionar usuarios y mantener la tienda organizada.

---

## 💻 Tecnologías utilizadas

- **Frontend(para el panel de administración):** <br>
![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white) 
- **Backend(API para el panel de administración):** <br>
![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white) 
- **Base de datos:** <br>
![SQLite](https://img.shields.io/badge/sqlite-%2307405e.svg?style=for-the-badge&logo=sqlite&logoColor=white) 

---

## 🛠️ Funcionalidades principales del Panel de Administración

- **Gestión de productos:** Agregar, editar y eliminar productos retro (consolas, juegos, merchandising, etc.)
- **Gestión de categorías:** Agregar, editar y eliminar categorías (consolas, juegos, accesorios, merchandising, etc.)
- **Gestión de usuarios:** Ver y gestionar usuarios registrados, asignar roles (administrador, cliente, etc.)
- **Gestión de pedidos:** Visualizar y gestionar los pedidos realizados por los usuarios.
- **Reportes y análisis:** Visualizar estadísticas y reportes de ventas, productos más vendidos, etc.
- **Autenticación:** Panel de acceso seguro para administradores con roles específicos.

---

## 🎯 Objetivo

El objetivo principal de este panel de administración es facilitar la gestión de **El Cartucho**, permitiendo a los administradores realizar un seguimiento detallado de los productos, usuarios y pedidos, mientras mantienen la tienda en funcionamiento de manera eficiente.

---

## 🌱 Cómo comenzar

### Requisitos previos

1. Tener instalado **PHP**, **Composer** y **Node.js**.
2. Tener configurado **SQLite3** para la base de datos.
3. Clonar el repositorio y seguir los siguientes pasos:

# 🚀 Pasos para levantar el proyecto

## 1. Clonar el repositorio

```bash
git clone git@github.com:Bikutah/El-Cartucho-BACKEND.git
cd El-Cartucho-BACKEND
```

## 2. Instalar las dependencias del backend

```bash
composer install
```

## 3. Configurar el archivo `.env`

- Copiar el archivo `.env-example` como `.env`.
- Configurar la API_KEY

## 4. Ejecutar el proyecto

```bash
php artisan serve
```
