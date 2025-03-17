import mysql.connector

class DatabaseController:
    # Función para inicializar la conexion a la base de datos
    def __init__(self):
        self.db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="Doblemanuel0426.",
            database="BlueLock"
        )
        # Cursor para ejecutar comandos SQL
        self.cursor = self.db.cursor()

    # Función para insertar las estadísticas de los jugadores
    def insert_stats(self, stats_data):
        #Inserta múltiples registros en la tabla Stats
        query = """
        INSERT INTO Stats (player_id, def, spd, off, pass, drb, shoot)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        values = [(entry["player_id"], entry["def"], entry["spd"], entry["off"],
                   entry["pass"], entry["drb"], entry["shoot"]) for entry in stats_data]

        # Ejecuta la consulta
        self.cursor.executemany(query, values)
        self.db.commit()  # Guarda los cambios en la base de datos

    # Función para insertar los jugadores
    def insert_players(self, players_data):
        # Inserta jugadores en la tabla Player
        query = """
        INSERT INTO Player (player_id, name, surname, birthday, height)
        VALUES (%s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE name=name
        """
        values = [(entry["player_id"], entry["nombre"], entry["apellido"], entry["fecha_nacimiento"],
                   entry["altura"]) for entry in players_data]

        # Ejecuta la consulta
        self.cursor.executemany(query, values)
        self.db.commit()

    # Función para cerrar la conexión con MySQL
    def close_connection(self):
        self.cursor.close()
        self.db.close()
