# 📦 Dokumentace: Vytváření čárového kódu pro skladové položky

Tento dokument popisuje strukturu dat v čárovém kódu používaném při manipulaci se zbožím. Systém podporuje různé typy pohybu zboží mezi sklady, zákazníky či při vyřazení.

## 🧾 Obsah čárového kódu

Každý čárový kód obsahuje následující data, ve specifikovaném pořadí:

| Pořadí | Název pole             | Popis |
|--------|------------------------|--------|
| 1.     | **movement_type**      | Typ pohybu: `1 = odeslání`, `2 = příjem`, `3 = přesun`, `4 = zničení` |
| 2.     | **item_type_id**       | Číselné ID typu položky (např. `101` pro "USB kabel") |
| 3.     | **from_warehouse_id**  | ID skladu, odkud položka odchází *(nebo `0` pokud není relevantní)* |
| 4.     | **to_warehouse_id**    | ID cílového skladu *(nebo `0` pokud jde např. k zákazníkovi)* |
| 5.     | **amount_in_package**  | Počet kusů v jednom balení (např. 25) |
| 6.     | **specific_item_id**   | Konkrétní ID fyzické položky *(nebo `0` pokud se jedná o novou položku)* |
| 7.     | **attributes** _(volitelně)_ | Vlastní atributy položky, serializované na konec – např. barva, rozměr, šarže |

> 🔁 **Poznámka**: Všechna pole 1–6 mohou být převedena na čistě číselnou podobu pro případ poruchy skeneru. V takovém případě musí systém umět čísla dekódovat zpět (např. mapování `movement_type = 1 → SEND`).

## 🛠️ Příklad dat v JSON formátu

```json
{
  "movement_type": 3,
  "item_type_id": 101,
  "from_warehouse_id": 12,
  "to_warehouse_id": 0,
  "amount_in_package": 50,
  "specific_item_id": 987654,
  "attributes": {
    "barva": "modrá",
    "délka": "1.5m"
  }
}
```

## 🔄 Možná číselná reprezentace pro skenování

```
3|101|12|0|50|987654|blue;1.5m;
```
## 🏷️ Příklad čárového kódu

![Barcode image](./barcode.gif "Příklad čárového kódu")

> 🔐 Atributy jsou vždy umístěny na konci a odděleny středníky, aby nebyly překážkou pro fixní parser.

---

## 📌 Shrnutí

- **Atributy jsou volitelné a vždy poslední.**
- **Všechna pole kromě atributů mohou být reprezentována čísly**.
- **`movement_type` určuje kontext operace** – je klíčový pro správné zpracování čárového kódu.
- **Systém by měl validovat strukturu kódu podle počtu polí a datových typů**.
