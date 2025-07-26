import axios from "axios";
import { defineStore } from "pinia";
import { ref } from "vue";

export const useUserStore = defineStore("user", () => {
    const user = ref(null);

    async function getUser() {
        await axios
            .get("/user")
            .then((response) => {
                setUser(response.data);
            })
            .catch((error) => {
                console.log(error);
            });
    }

    function setUser(newUser) {
        user.value = newUser;
    }

    function logout() {
        user.value = null;

        axios.post("/logout");
    }

    return { user, getUser, logout, setUser };
});
