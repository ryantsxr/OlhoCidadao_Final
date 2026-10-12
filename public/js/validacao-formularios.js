/*
 * Validação e sanitização dos formulários do Olho Cidadão.
 * A validação do navegador melhora a experiência, mas o servidor também valida tudo.
 */
(function () {
    'use strict';

    function somenteDigitos(valor) {
        return String(valor || '').replace(/\D/g, '');
    }

    function mascaraTelefone(valor) {
        const d = somenteDigitos(valor).slice(0, 11);
        if (d.length <= 10) {
            return d.replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*$/, function (_, a, b, c) {
                return a + (b ? ' ' + b : '') + (c ? '-' + c : '');
            }).trim().replace(/^([0-9]{2}) /, '($1) ');
        }
        return d.replace(/^(\d{2})(\d{5})(\d{0,4}).*$/, '($1) $2-$3').replace(/-$/, '');
    }

    function mascaraCPF(valor) {
        const d = somenteDigitos(valor).slice(0, 11);
        return d.replace(/^(\d{3})(\d{0,3})(\d{0,3})(\d{0,2}).*$/, function (_, a,b,c,e) {
            return a + (b ? '.' + b : '') + (c ? '.' + c : '') + (e ? '-' + e : '');
        });
    }

    function mascaraCEP(valor) {
        const d = somenteDigitos(valor).slice(0, 8);
        return d.replace(/^(\d{5})(\d{0,3}).*$/, '$1' + (d.length > 5 ? '-' + d.slice(5) : ''));
    }

    // Validação oficial dos dois dígitos verificadores do CPF.
    // Também rejeita sequências com todos os números iguais.
    function cpfValido(valor) {
        const cpf = somenteDigitos(valor);
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

        for (let tamanho = 9; tamanho <= 10; tamanho++) {
            let soma = 0;
            for (let i = 0; i < tamanho; i++) {
                soma += Number(cpf.charAt(i)) * ((tamanho + 1) - i);
            }
            const digito = ((10 * soma) % 11) % 10;
            if (Number(cpf.charAt(tamanho)) !== digito) return false;
        }
        return true;
    }

    function aplicarMascara(input) {
        const mascara = input.dataset.mask;
        if (!mascara) return;
        const aplicar = () => {
            if (mascara === 'telefone') input.value = mascaraTelefone(input.value);
            if (mascara === 'cpf') input.value = mascaraCPF(input.value);
            if (mascara === 'cep') input.value = mascaraCEP(input.value);
        };
        input.addEventListener('input', aplicar);
        input.addEventListener('blur', aplicar);
        aplicar();
    }

    function mostrarErro(input, mensagem) {
        input.setCustomValidity(mensagem);
        input.reportValidity();
        input.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-mask]').forEach(aplicarMascara);
        document.querySelectorAll('input, textarea, select').forEach(function (input) {
            input.addEventListener('input', function () { input.setCustomValidity(''); });
            input.addEventListener('change', function () { input.setCustomValidity(''); });
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                // Sanitização: remove espaços acidentais do começo/fim dos campos de texto.
                form.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(function (input) {
                    input.value = input.value.trim();
                });

                form.querySelectorAll('[data-mask="telefone"], [data-mask="cpf"], [data-mask="cep"]').forEach(function (input) {
                    input.value = somenteDigitos(input.value);
                });

                const email = form.querySelector('input[type="email"]');
                if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                    event.preventDefault();
                    mostrarErro(email, 'Informe um e-mail válido.');
                    return;
                }

                const senha = form.querySelector('#senha');
                const confirmar = form.querySelector('#confirmar_senha');
                if (senha && confirmar && senha.value !== confirmar.value) {
                    event.preventDefault();
                    mostrarErro(confirmar, 'As senhas não são iguais.');
                    return;
                }

                const novaSenha = form.querySelector('#novaSenha');
                const confirmaNova = form.querySelector('#confirmaNovaSenha');
                if (novaSenha && confirmaNova && novaSenha.value !== confirmaNova.value) {
                    event.preventDefault();
                    mostrarErro(confirmaNova, 'As senhas não são iguais.');
                    return;
                }

                const telefone = form.querySelector('[data-mask="telefone"]');
                if (telefone && telefone.required && !telefone.value) {
                    event.preventDefault();
                    mostrarErro(telefone, 'O telefone é obrigatório.');
                    return;
                }
                if (telefone && telefone.value) {
                    const d = somenteDigitos(telefone.value);
                    if (d.length !== 10 && d.length !== 11) {
                        event.preventDefault();
                        mostrarErro(telefone, 'Informe um telefone válido com DDD.');
                        return;
                    }
                }

                const cpf = form.querySelector('[data-mask="cpf"]');
                if (cpf && cpf.required && !cpf.value) {
                    event.preventDefault();
                    mostrarErro(cpf, 'O CPF é obrigatório.');
                    return;
                }
                if (cpf && cpf.value) {
                    const d = somenteDigitos(cpf.value);
                    if (!cpfValido(d)) {
                        event.preventDefault();
                        mostrarErro(cpf, 'Informe um CPF válido.');
                        return;
                    }
                }

                const cep = form.querySelector('[data-mask="cep"]');
                if (cep && cep.required && !cep.value) {
                    event.preventDefault();
                    mostrarErro(cep, 'O CEP é obrigatório.');
                    return;
                }
                if (cep && cep.value && somenteDigitos(cep.value).length !== 8) {
                    event.preventDefault();
                    mostrarErro(cep, 'Informe um CEP válido.');
                }
            });
        });
    });
})();
