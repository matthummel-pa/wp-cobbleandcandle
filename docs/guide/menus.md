# Menus

A **menu** (Tasting, À la carte, Cellar, Sunday lunch…) has **sections** (To begin, Mains, Puddings…), and each **dish** belongs to one or more menus and sections.

## Edit a dish

**Food & drink → All menu items → (dish)**. Write the description under **Excerpt** and use the **Price & details** panel on the right:

- **Price**, or **Sizes** for glass/bottle or starter/main.
- **Dietary**: vegetarian, vegan, gluten-free, spicy. These drive the menu filters and the allergen key.
- **Flag**: a short badge such as *Chef’s pick* or *Popular*.
- **Show in Chef’s picks**: puts the dish on the home page.
- **Served at**: limit a dish to some houses (empty = every house).
- **Menus / Menu sections** panels: where the dish appears. Order within a section by the *Order* field.

## Import a whole menu from a spreadsheet

**Food & drink → Import / export.**

1. Click **Download a sample file** (or **Export menus** to start from what you have).
2. Edit it in Excel, Numbers or Google Sheets. Columns: `menu, section, name, description, price, sizes, diet, flag, chef_pick, menu_intro`. Only **menu, section, name** are required.
   - `sizes`: `Glass: $12 | Bottle: $48`
   - `diet`: `v, vg, gf, spicy`, or words like `vegan, gluten-free`
   - `chef_pick`: `yes` / `no`
3. Save as CSV and upload. You’ll see a **preview**: which dishes are new, which will update, and any rows with problems.
4. Click **Import these dishes**.

Dishes are matched by **name within the same menu and section**. Nothing is ever deleted. Columns you leave out of the file are left alone on existing dishes, and unpublished dishes stay unpublished. Tick *Leave existing dishes as they are* to only add new ones.
