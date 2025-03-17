from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from DatabaseController import DatabaseController
import os
import requests
import os
import requests

"""
Lista de personajes con sus metadatos básicos

Cada entrada contiene:
- nombre: Nombre del personaje
- apellido: Apellido del personaje
- url: Enlace a su página en la wikia de Blue Lock
"""
personajes = [
    {
        "nombre": "Yoichi",
        "apellido": "Isagi",
        "url": "https://bluelock.fandom.com/wiki/Yoichi_Isagi"
    },
    {
        "nombre": "Meguru",
        "apellido": "Bachira",
        "url": "https://bluelock.fandom.com/wiki/Meguru_Bachira"
    },
    {
        "nombre": "Rensuke",
        "apellido": "Kunigami",
        "url": "https://bluelock.fandom.com/wiki/Rensuke_Kunigami"
    },
    {
        "nombre": "Hyoma",
        "apellido": "Chigiri",
        "url": "https://bluelock.fandom.com/wiki/Hyoma_Chigiri"
    },
    {
        "nombre": "Gin",
        "apellido": "Gagamaru",
        "url": "https://bluelock.fandom.com/wiki/Gin_Gagamaru"
    },
    {
        "nombre": "Jingo",
        "apellido": "Raichi",
        "url": "https://bluelock.fandom.com/wiki/Jingo_Raichi"
    },
    {
        "nombre": "Gurimu",
        "apellido": "Igarashi",
        "url": "https://bluelock.fandom.com/wiki/Gurimu_Igarashi"
    },
    {
        "nombre": "Shoei",
        "apellido": "Barou",
        "url": "https://bluelock.fandom.com/wiki/Shoei_Baro"
    },
    {
        "nombre": "Ikki",
        "apellido": "Niko",
        "url": "https://bluelock.fandom.com/wiki/Ikki_Niko"
    },
    {
        "nombre": "Junichi",
        "apellido": "Wanima",
        "url": "https://bluelock.fandom.com/wiki/Junichi_Wanima"
    },
    {
        "nombre": "Seishiro",
        "apellido": "Nagi",
        "url": "https://bluelock.fandom.com/wiki/Seishiro_Nagi"
    },
    {
        "nombre": "Reo",
        "apellido": "Mikage",
        "url": "https://bluelock.fandom.com/wiki/Reo_Mikage"
    },
    {
        "nombre": "Zantetsu",
        "apellido": "Tsurugi",
        "url": "https://bluelock.fandom.com/wiki/Zantetsu_Tsurugi"
    },
    {
        "nombre": "Rin",
        "apellido": "Itoshi",
        "url": "https://bluelock.fandom.com/wiki/Rin_Itoshi"
    },
    {
        "nombre": "Jyubei",
        "apellido": "Aryu",
        "url": "https://bluelock.fandom.com/wiki/Jyubei_Aryu"
    },
    {
        "nombre": "Aoshi",
        "apellido": "Tokimitsu",
        "url": "https://bluelock.fandom.com/wiki/Aoshi_Tokimitsu"
    },
    {
        "nombre": "Tabito",
        "apellido": "Karasu",
        "url": "https://bluelock.fandom.com/wiki/Tabito_Karasu"
    },
    {
        "nombre": "Eita",
        "apellido": "Otoya",
        "url": "https://bluelock.fandom.com/wiki/Eita_Otoya"
    },
    {
        "nombre": "Kenyu",
        "apellido": "Yukimiya",
        "url": "https://bluelock.fandom.com/wiki/Kenyu_Yukimiya"
    },
    {
        "nombre": "Yo",
        "apellido": "Hiori",
        "url": "https://bluelock.fandom.com/wiki/Yo_Hiori"
    },
    {
        "nombre": "Nijiro",
        "apellido": "Nanase",
        "url": "https://bluelock.fandom.com/wiki/Nijiro_Nanase"
    },
    {
        "nombre": "Ranze",
        "apellido": "Kurona",
        "url": "https://bluelock.fandom.com/wiki/Ranze_Kurona"
    },
    {
        "nombre": "Jin",
        "apellido": "Kiyora",
        "url": "https://bluelock.fandom.com/wiki/Jin_Kiyora"
    },
    {
        "nombre": "Ryusei",
        "apellido": "Shidou",
        "url": "https://bluelock.fandom.com/wiki/Ryusei_Shido"
    },
    {
        "nombre": "Oliver",
        "apellido": "Aiku",
        "url": "https://bluelock.fandom.com/wiki/Oliver_Aiku"
    },
    {
        "nombre": "Shuto",
        "apellido": "Sendo",
        "url": "https://bluelock.fandom.com/wiki/Shuto_Sendo"
    },
    {
        "nombre": "Sae",
        "apellido": "Itoshi",
        "url": "https://bluelock.fandom.com/wiki/Sae_Itoshi"
    },
    {
        "nombre": "Michael",
        "apellido": "Kaiser",
        "url": "https://bluelock.fandom.com/wiki/Michael_Kaiser"
    },
    {
        "nombre": "Alexis",
        "apellido": "Ness",
        "url": "https://bluelock.fandom.com/wiki/Alexis_Ness"
    },
    {
        "nombre": "Don",
        "apellido": "Lorenzo",
        "url": "https://bluelock.fandom.com/wiki/Don_Lorenzo"
    },
    {
        "nombre": "Charles",
        "apellido": "Chevalier",
        "url": "https://bluelock.fandom.com/wiki/Charles_Chevalier"
    }
]

# Ruta de imágenes
IMAGE_FOLDER = "public/assets/img/"
os.makedirs(IMAGE_FOLDER, exist_ok=True)  # Asegura que la carpeta exista

db_controller = DatabaseController()  # Conexión con la base de datos

def scraperStats():
    """
    Obtiene las estadísticas de los jugadores desde la web de Blue Lock

    Utiliza Selenium para:
    - Automatizar la navegación en el sitio web
    - Extraer valores numéricos de las estadísticas
    - Almacenar los datos en la base de datos mediante DatabaseController

    Returns:
        list: Lista de diccionarios con las estadísticas de cada jugador

    Raises:
        WebDriverException: Si hay problemas con el navegador o la conexión
        DatabaseError: Si falla la inserción en la base de datos
    """
    driver = webdriver.Chrome()
    driver.get("https://blue-lock-marcelones.vercel.app/")

    wait = WebDriverWait(driver, 3)
    buttons = driver.find_elements(By.CSS_SELECTOR, "button.select-none")

    stats_data = []

    for player_id, button in enumerate(buttons, start=1):
        button.click()

        wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".flex.w-\\[70px\\].justify-center.items-center.bg-color1.mx-1.font-roboto.text-white.font-bold")))

        stats = driver.find_elements(By.CSS_SELECTOR, ".flex.w-\\[70px\\].justify-center.items-center.bg-color1.mx-1.font-roboto.text-white.font-bold")
        stat_values = [int(stat.text) for stat in stats]

        stats_data.append({
            "player_id": player_id,
            "spd": stat_values[0],
            "def": stat_values[1],
            "pass": stat_values[2],
            "drb": stat_values[3],
            "shoot": stat_values[4],
            "off": stat_values[5]
        })

    driver.quit()

    # Insertar estadísticas en MySQL
    db_controller.insert_stats(stats_data)
    print("Estadísticas insertadas en MySQL")

def scraperPlayers():
    """
    Descarga y almacena imágenes de los personajes de Blue Lock

    Funcionalidades principales:
    - Descarga imágenes desde URLs especificadas en la lista 'personajes'
    - Guarda las imágenes en la carpeta 'public/assets/img/'
    - Registra las rutas de las imágenes en la base de datos

    Raises:
        requests.exceptions.RequestException: Si falla la descarga de imágenes
        IOError: Si hay problemas guardando los archivos locales
    """
    driver = webdriver.Chrome()
    wait = WebDriverWait(driver, 5)

    players_data = []

    for player_id, personaje in enumerate(personajes, start=1):
        driver.get(personaje["url"])

        try:
            birthday_element = wait.until(EC.presence_of_element_located(
                (By.CSS_SELECTOR, 'div[data-source="birthday"] .pi-data-value')
            ))
            driver.execute_script("arguments[0].querySelectorAll('sup').forEach(e => e.remove());", birthday_element)
            birthday = birthday_element.text.strip()
        except:
            birthday = "N/A"

        try:
            height_element = wait.until(EC.presence_of_element_located(
                (By.CSS_SELECTOR, 'div[data-source="height"] .pi-data-value')
            ))
            driver.execute_script("arguments[0].querySelectorAll('sup').forEach(e => e.remove());", height_element)
            height_raw = height_element.text.strip()
            height_parts = height_raw.split(" ")
            height = f"{height_parts[0]} {height_parts[1]}" if len(height_parts) >= 2 else "N/A"
        except:
            height = "N/A"

        try:
            image_element = wait.until(EC.presence_of_element_located(
                (By.CSS_SELECTOR, 'figure.pi-item.pi-image img')
            ))
            image_url = image_element.get_attribute("src")

            image_filename = f"{player_id}.jpg"
            image_path = os.path.join(IMAGE_FOLDER, image_filename)

            if not os.path.exists(image_path):
                response = requests.get(image_url, stream=True)
                if response.status_code == 200:
                    with open(image_path, "wb") as file:
                        for chunk in response.iter_content(1024):
                            file.write(chunk)
        except:
            image_path = "N/A"

        players_data.append({
            "player_id": player_id,
            "nombre": personaje["nombre"],
            "apellido": personaje.get("apellido", "N/A"),
            "fecha_nacimiento": birthday,
            "altura": height,
            "imagen_local": image_path
        })

    driver.quit()

    # Insertar jugadores en MySQL
    db_controller.insert_players(players_data)
    print("Jugadores insertados en MySQL")

# Ejecutar scrapers e insertar datos en la base de datos
scraperPlayers()
scraperStats()

# Cerrar conexión a la base de datos
db_controller.close_connection()
