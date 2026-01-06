<style>
    .menu-container {
        display: flex;
        gap: 2rem;
    }

    a {
        color: green;
    }

    ul li a[data-is-active]:hover {
        color: orange;
        font-weight: bold;
    }

    ul li a[data-is-active="1"] {
        color: var(--orange-600, orange);
        font-weight: bold;
    }
</style>
<script src="//cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
