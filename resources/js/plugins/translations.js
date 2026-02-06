export default function (Alpine) {
    Alpine.magic("t", () => {
        return (key) => {
            const messages = window.translations ?? {};
            return messages.titles?.[key] ?? key;
        };
    });

    Alpine.magic("e", () => {
        return (key) => {
            const messages = window.translations ?? {};
            return messages.errors?.[key] ?? key;
        };
    });

    Alpine.magic("s", () => {
        return (key) => {
            const messages = window.translations ?? {};
            return messages.success?.[key] ?? key;
        };
    });

    Alpine.magic("l", () => {
        return (key) => {
            const messages = window.translations ?? {};
            return messages.labels?.[key] ?? key;
        };
    });
}
