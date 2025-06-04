#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

#define DHTPIN 4
#define DHTTYPE DHT11

// WiFi credentials
const char WIFI_SSID[] = "GN";
const char WIFI_PASSWORD[] = "12345678";

// Server path
String HOST_NAME = "http://192.168.113.232";
String PATH_NAME = "/collect_temp.php";

// DHT Sensor
DHT dht(DHTPIN, DHTTYPE);

// Pin Definitions
#define BUZZER_PIN 5
#define BLUE_LED 12
#define GREEN_LED 13
#define YELLOW_LED 14
#define RED_LED 15
#define BUTTON_PIN 27  // Add a push button to GPIO 27

unsigned long lastCheck = 0;
const unsigned long interval = 5000; // 5 seconds

void setup() {
  Serial.begin(9600);
  dht.begin();

  // Setup pin modes
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(BLUE_LED, OUTPUT);
  pinMode(GREEN_LED, OUTPUT);
  pinMode(YELLOW_LED, OUTPUT);
  pinMode(RED_LED, OUTPUT);
  pinMode(BUTTON_PIN, INPUT_PULLUP); // Active LOW button

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.println("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.print("Connected to WiFi with IP: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  // 1. Check for 'c' in Serial Monitor
  if (Serial.available()) {
    char command = Serial.read();
    if (command == 'C' || command == 'c') {
      checkTemperature();
    }
  }

  // 2. Check if button is pressed (active LOW)
  if (digitalRead(BUTTON_PIN) == LOW) {
    checkTemperature();
    delay(300); // Debounce delay
  }

  // 3. Automatic every 5 seconds
  unsigned long currentMillis = millis();
  if (currentMillis - lastCheck >= interval) {
    lastCheck = currentMillis;
    checkTemperature();
  }
}

void checkTemperature() {
  float temperature = dht.readTemperature();

  if (isnan(temperature)) {
    Serial.println("Failed to read from DHT sensor!");
    return;
  }

  Serial.print("Temperature: ");
  Serial.print(temperature);
  Serial.println(" °C");

  indicateTemperature(temperature);

  // Send to server
  String queryString = "?temperature=" + String(temperature);
  HTTPClient http;
  http.begin(HOST_NAME + PATH_NAME + queryString);
  int httpCode = http.GET();

  if (httpCode > 0) {
    if (httpCode == HTTP_CODE_OK) {
      String payload = http.getString();
      Serial.println("Response from server: " + payload);
    } else {
      Serial.printf("[HTTP] GET... code: %d\n", httpCode);
    }
  } else {
    Serial.printf("[HTTP] GET... failed, error: %s\n", http.errorToString(httpCode).c_str());
  }

  http.end();
}

void indicateTemperature(float temp) {
  digitalWrite(BLUE_LED, LOW);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(YELLOW_LED, LOW);
  digitalWrite(RED_LED, LOW);

  int buzzDuration = 100;

  if (temp < 30) {
    digitalWrite(BLUE_LED, HIGH);
    buzzDuration = 500;
  } else if (temp >= 30 && temp <= 31) {
    digitalWrite(GREEN_LED, HIGH);
    buzzDuration = 1000;
  } else if (temp > 31 && temp <= 32) {
    digitalWrite(YELLOW_LED, HIGH);
    buzzDuration = 1500;
  } else {
    digitalWrite(RED_LED, HIGH);
    buzzDuration = 2000;
  }

  digitalWrite(BUZZER_PIN, HIGH);
  delay(buzzDuration);
  digitalWrite(BUZZER_PIN, LOW);
}
