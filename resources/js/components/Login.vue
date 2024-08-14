<template>
  <v-container
    fluid
    class="fill-height d-flex align-center justify-center login-container"
  >
    <v-row class="justify-center">
      <v-col cols="12" md="6" lg="4">
        <v-card class="elevation-3 text-center">
          <v-img
            src="/uploads/home_images/UG.png"
            contain
            height="120px"
            class="my-3"
          ></v-img>
          <v-card-title class="headline font-weight-bold">
            SWGCC
          </v-card-title>
          <v-card-text>
            <v-form @submit.prevent="login" ref="form">
              <v-text-field
                v-model="cedula"
                label="Cédula"
                type="text"
                required
                :rules="cedulaRules"
                outlined
                dense
              ></v-text-field>
              <v-text-field
                v-model="password"
                label="Contraseña"
                type="password"
                required
                :rules="passwordRules"
                outlined
                dense
              ></v-text-field>
              <v-btn
                type="submit"
                color="blue-darken-4"
                block
                large
                class="my-4"
              >
                Iniciar Sesión
              </v-btn>
            </v-form>
            <v-alert
              v-if="error"
              type="error"
              dismissible
              class="mt-1"
              variant="elevated"
              @input="error = false"
            >Usuario o contraseña incorrectos.
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import axios from "axios";

export default {
  name: "Login",
  data() {
    return {
      cedula: "",
      password: "",
      error: false,
      cedulaRules: [
        (v) => !!v || "La cédula es obligatoria",
        (v) => this.validateCedula(v) || "Cédula inválida",
      ],
      passwordRules: [
        (v) => !!v || "La contraseña es obligatoria",
        (v) => v.length >= 8 || "La contraseña debe tener al menos 8 caracteres",
      ],
    };
  },
  methods: {
    validateCedula(cedula) {
      if (cedula.length !== 10) return false;
      const digits = cedula.split("").map(Number);
      const provinceCode = digits[0] * 10 + digits[1];
      if (provinceCode < 1 || provinceCode > 24) return false;
      const lastDigit = digits.pop();
      let sum = 0;
      digits.forEach((digit, index) => {
        if (index % 2 === 0) {
          const product = digit * 2;
          sum += product > 9 ? product - 9 : product;
        } else {
          sum += digit;
        }
      });
      const checkDigit = (10 - (sum % 10)) % 10;
      return checkDigit === lastDigit;
    },
    async login() {
      if (this.$refs.form.validate()) {
        try {
          const response = await axios.post("/login", {
            cedula: this.cedula,
            password: this.password,
          });

          if (response.status === 200) {
            const role = response.data.role;
            const token = response.data.token;

            // Guardar el token en localStorage
            localStorage.setItem("authToken", token);

            // Configurar el encabezado Authorization para futuras solicitudes
            axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

            if (role === "Administrador") {
              this.$router.push("/admin");
            } else {
              this.$router.push("/courses");
            }
          }
        } catch (error) {
          this.error = true;
        }
      }
    },
  },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap");

.login-container {
  background-color: #f5f5f5;
  padding: 30px;
  min-height: 100vh;
}

.headline {
  font-family: "Roboto", sans-serif;
}

v-card {
  border-radius: 12px;
  font-family: "Roboto", sans-serif;
  padding: 20px;
}

v-btn {
  font-family: "Roboto", sans-serif;
  font-weight: 500;
}
</style>
