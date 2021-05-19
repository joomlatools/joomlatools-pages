---
route:
    - login/[*]?
    - log-out/[*]?
layout: default
name: Login
title: Login
summary: The description for the login
process:
    decorate: true
---

<style>

    form {
        border: 1px solid #e9e9e9;
        border-radius: 5px;
        box-shadow: 2px 2px 10px #f4f4f4;
        display: block;
        padding: 1.5rem;
    }

    input {
        border: 1px solid #e9e9e9;
        border-radius: 5px;
        margin-bottom: 1rem;
        padding: 0.4rem 0.8rem;
    }

    label {
        font-weight: bold;
        margin-bottom: 0.2rem;
    }

    button {
        border-radius: 5px;
        display: inline-block;
        font-size: medium;
        font-weight: bold;
        margin: 0.5rem 0;
        padding: 0.5rem 1rem;
    }

    button:hover {
        cursor: pointer;
        filter: brightness(1.2);
    }

    button {
        background-color: rgba(0,173,239);
        color: #fff;
    }

    .nav {
        margin-top: 2em;
    }

</style>

<ktml:content>