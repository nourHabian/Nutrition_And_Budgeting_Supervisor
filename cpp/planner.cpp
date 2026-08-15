#include <bits/stdc++.h>
#include <windows.h>
#include <fstream>
#include "nlohmann/json.hpp"
using namespace std;
using json = nlohmann::json;
typedef long long ll;

enum class PrepTime : uint8_t {
    Short,
    Medium,
    Long
};
enum class Difficulty : uint8_t {
    Easy,
    Medium,
    Hard
};
enum class Unit : uint8_t {
    Gram,
    Piece
};
enum class Season {
    Summer,
    Winter,
    Autumn,
    Spring,
    All_Year
};

struct Ingredient {
    int id;
    string name;
    int price;
    double protein;
    double carbohydrates;
    double fiber;
};
struct MealIngredient {
    int ingredient_id;
    int substitution_id = -1;
    int quantity;
    Unit unit;
    bool is_required;
    bool is_substitution = false;
};
struct Meal {
    int id;
    int parent_id = -1;
    string name;
    int servings;
    PrepTime prep_time;
    Difficulty difficulty_level;
    Season season;
    vector <MealIngredient> ingredients;
    int price = 0;
    int score = 0;
    int value = 0;
    double protein = 0;
    double carbohydrates = 0;
    double fiber = 0;
    double nutrition = 0;
};
struct InventoryItem {
    int ingredient_id;
    int quantity;
    Unit unit;
};
struct RequiredMeal {
    int parent_id;
    int id; // the exact expanded variant id from a previous plan
};
struct UserProfile {
    unordered_set <int> allergies; // id of ingredient
    unordered_set <int> disliked_ingredients; // id of ingredient
    unordered_set <int> disliked_meals; // id of meal
    unordered_set <int> liked_meals; // id of meal
    vector <pair <int, int>> recent_meals;
};
struct Inventory {
    unordered_map <int, InventoryItem> available_ingredients;
    unordered_set <int> always_available_ingredients; // IDs of ingredients
};
struct Constraints {
    PrepTime prep_time;
    Season season;
    int servings;
    int number_of_meals;
    int budget;
};
struct UserData {
    UserProfile profile;
    Inventory inventory;
    Constraints constraints;
    vector <RequiredMeal> required_meals;
};


unordered_map <string, int> ingredientNameToId;
vector <Ingredient> ingredientsById;

unordered_map <string, int> mealNameToId;
vector <Meal> mealsById;

vector <Meal> original_meals;
vector <Meal> expanded_meals;
int expanded_meals_cnt;
vector <Meal> filtered_meals;
unordered_map <int, vector <Meal>> candidates;
UserData user;
double average_nutrition;
const int MAX_MEALS = 7;
const int MAX_BUDGET = 700;
const int UNIT_DIM = 11;
const int UNIT_CAP = 10;
const int NEG_INF = -1000000000;
pair <int, vector <int>> dp[2][MAX_MEALS + 1][MAX_BUDGET + 1][UNIT_DIM][UNIT_DIM][UNIT_DIM];
const int PROTEIN_T1 = 10, PROTEIN_T2 = 20;
const int CARBO_T1 = 20, CARBO_T2 = 40;
const int FIBER_T1 = 3,  FIBER_T2 = 6;

const int MAX_PER_PARENT = 3;
const int MAX_PER_MAIN_INGREDIENT = 2;

const int COMPLETENESS_BONUS = 15;
const int SUBSTITUTION_PENALTY = 8;

void extract_ingredient_data(string path) {
    ifstream file(path);
    if (!file.is_open())
        throw runtime_error("Cannot open " + path);
    json data;
    file >> data;
    ingredientsById.resize(data.size() + 10);
    for (const auto& item : data) {
        Ingredient ing;
        ing.id = item["ingredient_id"];
        ing.name = item["name"];
        ing.protein = item["protein"];
        ing.carbohydrates = item["carbohydrates"];
        ing.fiber = item["fiber"];
        ing.price = item["price"];
        ingredientNameToId[ing.name] = ing.id;
        ingredientsById[ing.id] = ing;
    }
}

void extract_meal_data(string path) {
    ifstream file(path);
    if (!file.is_open())
        throw runtime_error("Cannot open " + path);
    json data;
    file >> data;
    mealsById.resize(data.size() + 10);
    for (auto& recipe : data) {
        Meal meal;
        meal.id = recipe["recipe_id"];
        meal.name = recipe["recipe_name"];
        meal.servings = recipe["servings"];

        if (recipe["prep_time"] == "طويل")
            meal.prep_time = PrepTime::Long;
        else if (recipe["prep_time"] == "متوسط")
            meal.prep_time = PrepTime::Medium;
        else if (recipe["prep_time"] == "قليل")
            meal.prep_time = PrepTime::Short;

        if (recipe["difficulty_level"] == "مبتدئ")
            meal.difficulty_level = Difficulty::Easy;
        else if (recipe["difficulty_level"] == "متوسط")
            meal.difficulty_level = Difficulty::Medium;
        else if (recipe["difficulty_level"] == "محترف")
            meal.difficulty_level = Difficulty::Hard;

        if (recipe["seasonality"] == "الصيف")
            meal.season = Season::Summer;
        else if (recipe["seasonality"] == "الربيع")
            meal.season = Season::Spring;
        else if (recipe["seasonality"] == "الشتاء")
            meal.season = Season::Winter;
        else if (recipe["seasonality"] == "الخريف")
            meal.season = Season::Autumn;
        else if (recipe["seasonality"] == "مدار السنة")
            meal.season = Season::All_Year;

        for (auto& ing : recipe["ingredients"]) {
            MealIngredient ingredient;
            ingredient.ingredient_id = ingredientNameToId[ing["name"]];
            ingredient.quantity = ing["quantity"];
            ingredient.unit = Unit::Gram;
            if (ing["unit"] == "حبة")
                ingredient.unit = Unit::Piece;
            ingredient.is_required = (ing["necessity_level"] == "إلزامي");
            if (!ing["substitution"].is_null() && ing["substitution"] != "nan")
                ingredient.substitution_id = ingredientNameToId[ing["substitution"]];
            else
                ingredient.substitution_id = -1;
            meal.ingredients.push_back(ingredient);
        }
        mealNameToId[meal.name] = meal.id;
        mealsById[meal.id] = meal;
        original_meals.push_back(meal);
    }
}

vector <Meal> generate_meal_combinations(Meal& meal) {
    vector <vector <pair <bool, MealIngredient>>> choices;
    for (const auto& ing : meal.ingredients) {
        vector <pair <bool, MealIngredient>> cur;
        if (!ing.is_required)
            cur.push_back({false, MealIngredient()});
        if (ing.substitution_id != -1) {
            MealIngredient sub = ing;
            sub.ingredient_id = sub.substitution_id;
            sub.substitution_id = -1;
            sub.is_required = 1;
            sub.is_substitution = true;
            cur.push_back({true, sub});
        }
        MealIngredient original_ing = ing;
        original_ing.substitution_id = -1;
        original_ing.is_required = 1;
        original_ing.is_substitution = false;
        cur.push_back({true, original_ing});
        choices.push_back(cur);
    }

    // now i have all possible choices, it's time to actually generate the meals

    vector <Meal> result;
    vector <int> idx(choices.size(), 0);
    while (true) {
        Meal newMeal = meal;
        newMeal.parent_id = meal.id;
        newMeal.ingredients.clear();
        for (int i = 0; i < choices.size(); i++) {
            const auto& choice = choices[i][idx[i]];
            if (choice.first)
                newMeal.ingredients.push_back(choice.second);
        }
        result.push_back(newMeal);
        int pos = choices.size() - 1;
        while (pos >= 0) {
            idx[pos]++;
            if (idx[pos] < choices[pos].size())
                break;
            idx[pos] = 0;
            pos--;
        }
        if (pos < 0)
            break;
    }
    return result;
}

void expand_meals() {
    expanded_meals_cnt = original_meals.size() + 1;
    for (Meal meal : original_meals) {
        vector <Meal> new_meals = generate_meal_combinations(meal);
        for (Meal new_meal : new_meals) {
            new_meal.id = expanded_meals_cnt++;
            expanded_meals.push_back(new_meal);
        }
    }
    mealsById.resize(expanded_meals_cnt);
}

void extract_user_data(string path) {
    ifstream file(path);
    if (!file.is_open())
        throw runtime_error("Cannot open " + path);
    json data;
    file >> data;

    for (auto& a : data["profile"]["allergies"]) {
        user.profile.allergies.insert({ingredientNameToId[a["name"]]});
    }
    for (auto& d : data["profile"]["disliked_ingredients"]) {
        user.profile.disliked_ingredients.insert({ingredientNameToId[d["name"]]});
    }
    for (auto& m : data["profile"]["disliked_meals"]) {
        user.profile.disliked_meals.insert({mealNameToId[m["meal_name"]]});
    }
    for (auto& m : data["profile"]["liked_meals"]) {
        user.profile.liked_meals.insert({mealNameToId[m["meal_name"]]});
    }
    for (auto& m : data["profile"]["recent_meals"]) {
        int meal_id = mealNameToId[m["meal_name"]];
        int weeks_ago = m["weeks_ago"];
        user.profile.recent_meals.push_back({meal_id, weeks_ago});
    }

    for (auto& i : data["inventory"]["available_ingredients"]) {
        InventoryItem item;
        item.ingredient_id = ingredientNameToId[i["name"]];
        item.quantity = i["quantity"];
        item.unit = Unit::Gram;
        if (i["unit"] == "حبة")
            item.unit = Unit::Piece;
        user.inventory.available_ingredients[item.ingredient_id] = item;
    }
    for (auto& i : data["inventory"]["always_available_ingredients"]) {
        user.inventory.always_available_ingredients.insert({ingredientNameToId[i["name"]]});
    }

    if (data["constraints"]["prep_time"] == "طويل")
        user.constraints.prep_time = PrepTime::Long;
    else if (data["constraints"]["prep_time"] == "متوسط")
        user.constraints.prep_time = PrepTime::Medium;
    else if (data["constraints"]["prep_time"] == "قليل")
        user.constraints.prep_time = PrepTime::Short;

    if (data["constraints"]["season"] == "الصيف")
        user.constraints.season = Season::Summer;
    else if (data["constraints"]["season"] == "الربيع")
        user.constraints.season = Season::Spring;
    else if (data["constraints"]["season"] == "الشتاء")
        user.constraints.season = Season::Winter;
    else if (data["constraints"]["season"] == "الخريف")
        user.constraints.season = Season::Autumn;
    else if (data["constraints"]["season"] == "مدار السنة")
        user.constraints.season = Season::All_Year;

    user.constraints.servings = data["constraints"]["servings"];
    user.constraints.number_of_meals = data["constraints"]["number_of_meals"];
    user.constraints.budget = data["constraints"]["budget"];

    if (data.contains("required_meals")) {
        for (auto& rm : data["required_meals"]) {
            RequiredMeal req;
            req.parent_id = rm["parent_id"];
            req.id = rm["id"];
            user.required_meals.push_back(req);
        }
    }

}

void filter_meals() {
    /*
        i should compare the disliked meals names with the meal names i have, if two matched => remove meal
        i should make a for loop on ingredients, if an ingredient is in allergies or in disliked ingredients => remove meal
        i should check the time required for the meal and compare it with user time, if user time is less than meal time => remove meal
    */
    filtered_meals.clear();
    for (const Meal& meal : expanded_meals) {
        if (user.profile.disliked_meals.count(meal.parent_id))
            continue;
        if (meal.prep_time == PrepTime::Long && user.constraints.prep_time != PrepTime::Long)
            continue;
        if (meal.prep_time == PrepTime::Medium && user.constraints.prep_time == PrepTime::Short)
            continue;
        bool flag = 1;
        for (const MealIngredient& ing : meal.ingredients) {
            if (user.profile.allergies.count(ing.ingredient_id) || user.profile.disliked_ingredients.count(ing.ingredient_id)) {
                flag = 0;
                break;
            }
        }
        if (flag)
            filtered_meals.push_back(meal);
    }
}

void calculate_price_for_one_person(Meal& meal) {
    int total_price = 0;
    for (const MealIngredient& ing : meal.ingredients) {
        if (user.inventory.always_available_ingredients.count(ing.ingredient_id))
            continue;
        int requiredQty = ceil((ing.quantity * 1.0) / meal.servings);
        double quantityToBuy = requiredQty;
        auto it = user.inventory.available_ingredients.find(ing.ingredient_id);
        if (it != user.inventory.available_ingredients.end()) {
            const InventoryItem& owned = it -> second;
            if (owned.quantity >= requiredQty)
                quantityToBuy = 0;
            else
                quantityToBuy = requiredQty - owned.quantity;
        }
        const Ingredient& ingredient = ingredientsById[ing.ingredient_id];
        total_price += ingredient.price * quantityToBuy;
    }
    total_price = ((total_price + 999) / 1000) * 1000;
    meal.price = total_price;
}

void calculate_meal_nutrition_elements(Meal& meal) {
    meal.protein = 0;
    meal.carbohydrates = 0;
    meal.fiber = 0;
    for (const MealIngredient& ing : meal.ingredients) {
        const Ingredient& info = ingredientsById[ing.ingredient_id];
        double amount = ing.quantity;
        amount /= meal.servings;
        meal.protein += info.protein * amount;
        meal.carbohydrates += info.carbohydrates * amount;
        meal.fiber += info.fiber * amount;
    }
    meal.nutrition =
    meal.protein * 3.0 +
    meal.fiber * 2.0 +
    meal.carbohydrates;
}

void calculate_meal_score(Meal& meal) {
    int score = 100;

    // liked meal
    if (user.profile.liked_meals.count(meal.parent_id))
        score += 30;

    Meal originalIt = mealsById[meal.parent_id];
    int maxIngredients = (int)originalIt.ingredients.size();
    if (maxIngredients > 0) {
        double completeness = (double)meal.ingredients.size() / maxIngredients;
        score += (int) round(completeness * COMPLETENESS_BONUS);
    }

    // seasonal meals
    if (meal.season == user.constraints.season)
        score += 25;
    if (meal.season == Season::All_Year)
        score += 20;

    double available = 0;
    for (const MealIngredient& ing : meal.ingredients) {
        if (user.inventory.always_available_ingredients.count(ing.ingredient_id))
            available += 1;
        auto it = user.inventory.available_ingredients.find(ing.ingredient_id);
        if (it != user.inventory.available_ingredients.end() && it -> second.quantity > 0) {
            int requiredQuantity = ((ing.quantity * 1.0) / meal.servings) * user.constraints.servings;
            if (it -> second.quantity >= requiredQuantity) {
                available += 1;
            } else {
                available += (it -> second.quantity * 1.0) / requiredQuantity;
            }
        }
    }
    score += (available / meal.ingredients.size()) * 100;

    // new
    int substitutionCount = 0;
    for (const MealIngredient& ing : meal.ingredients)
        if (ing.is_substitution) substitutionCount++;
    score -= (int)round((substitutionCount * 1.0 / meal.ingredients.size()) * SUBSTITUTION_PENALTY);

    // recent meal penalty
    for (auto [meal_id, weeks] : user.profile.recent_meals) {
        if (meal_id != meal.parent_id)
            continue;
        if (weeks >= 1 && weeks <= 4)
            score -= (5 - weeks) * 10;
        break;
    }

    double nutrition_ratio = meal.nutrition / average_nutrition;
    if (nutrition_ratio > 1.0)
        score += min(15.0, (nutrition_ratio - 1) * 25);
    else
        score -= min(15.0, (1 - nutrition_ratio) * 25);

    meal.score = max(score, 0);
}


vector <int> mainIngredientsOf(const Meal& meal) {
    vector <int> ids;
    for (const MealIngredient& ing : meal.ingredients) {
        if (ing.is_required && ceil((ing.quantity * 1.0) / meal.servings) >= 75) {
            ids.push_back(ing.ingredient_id);
        }
    }
    return ids;
}

void get_top_100_meals() {
    average_nutrition = 0;
    for (Meal& meal : filtered_meals) {
        calculate_price_for_one_person(meal);
        calculate_meal_nutrition_elements(meal);
        average_nutrition += meal.nutrition;
    }
    average_nutrition /= filtered_meals.size();
    for (Meal& meal : filtered_meals) {
        calculate_meal_score(meal);
        double target = (double)user.constraints.budget / user.constraints.number_of_meals;
        double price_bonus;
        if (target == 0) {
            price_bonus = (meal.price == 0) ? 20 : -20;
        } else {
            double price_ratio = (meal.price * user.constraints.servings * 1.0) / target;
            if (price_ratio >= 0.8 && price_ratio <= 1.2)
                price_bonus = 20;
            else if (price_ratio >= 0.6 && price_ratio <= 1.5)
                price_bonus = 10;
            else if (price_ratio < 0.4 || price_ratio > 2.0)
                price_bonus = -20;
            else
                price_bonus = -10;
        }
        meal.value = meal.score + price_bonus;
    }
    sort(filtered_meals.begin(), filtered_meals.end(),
         [](const Meal& a, const Meal& b) {
             return a.value > b.value;
         });


    for (Meal meal : filtered_meals) {
        mealsById[meal.id] = meal;
    }

    candidates.clear();
    unordered_map <int, int> taken;
    unordered_map <int, int> mainIngredientTaken;

    int total_candidates = 0;

    for (const Meal& meal : filtered_meals) {
        if (taken[meal.parent_id] >= MAX_PER_PARENT)
            continue;
        vector <int> mainIng = mainIngredientsOf(meal);
        bool flag = 0;
        for (int mainId : mainIng) {
            if (taken[meal.parent_id] == 0 && mainIngredientTaken[mainId] >= MAX_PER_MAIN_INGREDIENT) {
                flag = 1;
                break;
            }
        }
        if (flag) continue;

        candidates[meal.parent_id].push_back(meal);
        if (taken[meal.parent_id] == 0) {
            for (int mainId : mainIng) {
                mainIngredientTaken[mainId]++;
            }
        }
        taken[meal.parent_id]++;
        total_candidates++;
        if (total_candidates == 100)
            break;
    }

}

int toUnits(double grams, int t1, int t2) {
    if (grams < t1) return 0;
    if (grams < t2) return 1;
    return 2;
}

void resetLayer(int layer, int K, int BUDGET) {
    for (int j = 0; j <= K; j++)
        for (int b = 0; b <= BUDGET; b++)
            for (int p = 0; p < UNIT_DIM; p++)
                for (int c = 0; c < UNIT_DIM; c++)
                    for (int f = 0; f < UNIT_DIM; f++) {
                        dp[layer][j][b][p][c][f].first = NEG_INF;
                        dp[layer][j][b][p][c][f].second.clear();
                    }
}

int requiredUnits(int K) {
    int target = (10 * K + 6) / 7; // ceil(10*K/7)
    return min(target, 2 * K);
}

int solveMealPlan(int K, int budgetForAllServings, int servings, vector <int>& bestPlanIds, long long& totalCostOut) {
    bestPlanIds.clear();

    if (K < 1 || K > MAX_MEALS || servings < 1) return NEG_INF;

    vector <Meal> required;
    unordered_set <int> requiredParents;
    for (const RequiredMeal& rm : user.required_meals) {
        if (requiredParents.count(rm.parent_id))
            continue;

        if (rm.id < 0 || rm.id >= (int)mealsById.size())
            continue;

        Meal meal = mealsById[rm.id];
        if (meal.parent_id != rm.parent_id)
            continue;

        if (user.profile.disliked_meals.count(meal.parent_id))
            continue;
        if (meal.prep_time == PrepTime::Long && user.constraints.prep_time != PrepTime::Long)
            continue;
        if (meal.prep_time == PrepTime::Medium && user.constraints.prep_time == PrepTime::Short)
            continue;
        bool unsafe = false;
        for (const MealIngredient& ing : meal.ingredients) {
            if (user.profile.allergies.count(ing.ingredient_id) || user.profile.disliked_ingredients.count(ing.ingredient_id)) {
                unsafe = true;
                break;
            }
        }
        if (unsafe)
            continue;

        bool foundInCandidates = false;
        auto candIt = candidates.find(rm.parent_id);
        if (candIt != candidates.end()) {
            for (const Meal& m : candIt->second) {
                if (m.id == rm.id) {
                    meal = m;
                    foundInCandidates = true;
                    break;
                }
            }
        }

        if (!foundInCandidates) {
            calculate_price_for_one_person(meal);
            calculate_meal_nutrition_elements(meal);
            calculate_meal_score(meal);
            double target = (double)user.constraints.budget / user.constraints.number_of_meals;
            double price_ratio = (meal.price * user.constraints.servings * 1.0) / target;
            double price_bonus;
            if (price_ratio >= 0.8 && price_ratio <= 1.2)
                price_bonus = 20;
            else if (price_ratio >= 0.6 && price_ratio <= 1.5)
                price_bonus = 10;
            else if (price_ratio < 0.4 || price_ratio > 2.0)
                price_bonus = -20;
            else
                price_bonus = -10;
            meal.value = meal.score + price_bonus;
            mealsById[meal.id] = meal;
        }

        requiredParents.insert(rm.parent_id);
        required.push_back(meal);
    }

    int requiredCount = (int) required.size();
    if (requiredCount > K) return NEG_INF;

    int budgetPerPerson = budgetForAllServings / servings;

    int priceGcd = 0;
    for (auto& kv : candidates)
        for (const Meal& m : kv.second)
            priceGcd = __gcd(priceGcd, m.price);
    for (const Meal& m : required)
        priceGcd = __gcd(priceGcd, m.price);
    if (priceGcd == 0) priceGcd = 1;

    int BUDGET = min(budgetPerPerson / priceGcd, MAX_BUDGET);
    if (BUDGET < 0) return NEG_INF;

    int requiredPriceScaled = 0, requiredValue = 0;
    int requiredProteinUnits = 0, requiredCarboUnits = 0, requiredFiberUnits = 0;
    vector <int> requiredIds;
    for (const Meal& m : required) {
        requiredPriceScaled += m.price / priceGcd;
        requiredValue += m.value;
        requiredProteinUnits = min(UNIT_CAP, requiredProteinUnits + toUnits(m.protein, PROTEIN_T1, PROTEIN_T2));
        requiredCarboUnits   = min(UNIT_CAP, requiredCarboUnits   + toUnits(m.carbohydrates, CARBO_T1, CARBO_T2));
        requiredFiberUnits   = min(UNIT_CAP, requiredFiberUnits   + toUnits(m.fiber, FIBER_T1, FIBER_T2));
        requiredIds.push_back(m.id);
    }
    if (requiredPriceScaled > BUDGET) return NEG_INF;

    vector <int> parentIds;
    parentIds.reserve(candidates.size());
    for (auto& kv : candidates)
        if (!requiredParents.count(kv.first))
            parentIds.push_back(kv.first);
    int N = (int)parentIds.size();

    int remainingSlots = K - requiredCount;
    if (N < remainingSlots) return NEG_INF;

    resetLayer(0, K, BUDGET);
    dp[0][requiredCount][requiredPriceScaled][requiredProteinUnits][requiredCarboUnits][requiredFiberUnits] = {requiredValue, requiredIds};

    for (int g = 0; g < N; g++) {
        int prev = g % 2;
        int cur  = (g + 1) % 2;

        for (int j = 0; j <= K; j++)
            for (int b = 0; b <= BUDGET; b++)
                for (int p = 0; p < UNIT_DIM; p++)
                    for (int c = 0; c < UNIT_DIM; c++)
                        for (int f = 0; f < UNIT_DIM; f++)
                            dp[cur][j][b][p][c][f] = dp[prev][j][b][p][c][f];

        for (const Meal& m : candidates[parentIds[g]]) {
            int price = m.price / priceGcd;
            if (price > BUDGET) continue;

            int pu = toUnits(m.protein, PROTEIN_T1, PROTEIN_T2);
            int cu = toUnits(m.carbohydrates, CARBO_T1, CARBO_T2);
            int fu = toUnits(m.fiber, FIBER_T1, FIBER_T2);
            int val = m.value;

            for (int j = 0; j < K; j++) {
                for (int b = 0; b + price <= BUDGET; b++) {
                    for (int p = 0; p < UNIT_DIM; p++) {
                        int np = min(UNIT_CAP, p + pu);
                        for (int c = 0; c < UNIT_DIM; c++) {
                            int nc = min(UNIT_CAP, c + cu);
                            for (int f = 0; f < UNIT_DIM; f++) {
                                const pair <int, vector <int>>& src = dp[prev][j][b][p][c][f];
                                if (src.first <= NEG_INF) continue;

                                int nf = min(UNIT_CAP, f + fu);
                                int newScore = src.first + val;

                                pair <int, vector <int>>& dst = dp[cur][j + 1][b + price][np][nc][nf];
                                if (newScore > dst.first) {
                                    dst.first = newScore;
                                    dst.second = src.second;
                                    dst.second.push_back(m.id);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    int finalLayer = N % 2;
    int target = requiredUnits(K);
    int bestScore, bestB;

    auto extract = [&](int pFrom, int cFrom, int fFrom) {
        bestScore = NEG_INF;
        bestB = 0;
        for (int b = 0; b <= BUDGET; b++) {
            for (int p = pFrom; p <= UNIT_CAP; p++) {
                for (int c = cFrom; c <= UNIT_CAP; c++) {
                    for (int f = fFrom; f <= UNIT_CAP; f++) {
                        const pair <int, vector <int>>& cell = dp[finalLayer][K][b][p][c][f];
                        if (cell.first > bestScore) {
                            bestScore = cell.first;
                            bestPlanIds = cell.second;
                            bestB = b;
                        }
                    }
                }
            }
        }
    };

    extract(target, target, target);

    if (bestScore <= NEG_INF && K <= 2) {
        extract(0, 0, 0);
    }

    if (bestScore > NEG_INF) {
        totalCostOut = (long long) bestB * priceGcd * servings;
    }

    return bestScore;
}


void writePlanToJson(const vector <int>& mealIds, const string& path) {
    json output = json::array();

    for (int id : mealIds) {
        const Meal& meal = mealsById[id];

        json mealJson;
        mealJson["meal_id"] = meal.parent_id;
        mealJson["expanded_meal_id"] = meal.id;
        mealJson["estimated_cost"] = meal.price * user.constraints.servings;

        json ingredientsJson = json::array();
        for (const MealIngredient& ing : meal.ingredients) {
            int perPersonQty = (int) ceil((double)ing.quantity / meal.servings);

            json ingJson;
            ingJson["ingredient_id"] = ing.ingredient_id;
            ingJson["quantity"] = perPersonQty * user.constraints.servings;
            ingJson["unit"] = (ing.unit == Unit::Piece) ? "حبة" : "غرام";
            ingredientsJson.push_back(ingJson);
        }
        mealJson["selected_ingredients"] = ingredientsJson;

        output.push_back(mealJson);
    }

    ofstream file(path);
    file << output.dump(4);
}

int main(int argc, char* argv[])
//int main()
{
    SetConsoleOutputCP(CP_UTF8);
    SetConsoleCP(CP_UTF8);
    ios_base::sync_with_stdio(0), cin.tie(0);

    if (argc < 3)
    {
        cerr << "Usage: planner.exe <input.json> <output.json>" << endl;
        return 1;
    }

    string inputPath = argv[1];
    string outputPath = argv[2];

//    string inputPath = "input.json";
//    string outputPath = "output.json";

    extract_ingredient_data("ingredients.json");
    extract_meal_data("recipes.json");
    expand_meals();
    extract_user_data(inputPath);
    filter_meals();
    get_top_100_meals();

//    for (pair <int, vector <Meal>> p : candidates)
//        for (Meal meal : p.second) {
//            cout << meal.name << " " << meal.score << " " << meal.value << "\n";
//            for (MealIngredient i : meal.ingredients) {
//                cout << ingredientsById[i.ingredient_id].name << " " << i.quantity << "\n";
//            }
//            cout << endl;
//        }

    vector <int> bestIds;
    long long totalCost = 0;
    int bestScore = solveMealPlan(user.constraints.number_of_meals, user.constraints.budget, user.constraints.servings, bestIds, totalCost);

    if (bestScore <= NEG_INF) {
        cout << "No valid meal plan found." << endl;
    } else {
        cout << "Best score: " << bestScore << endl;
        cout << "Total cost (all " << user.constraints.servings << " servings): " << totalCost
             << " / " << user.constraints.budget << endl;

        if (!user.required_meals.empty()) {
            int honored = 0;
            unordered_set<int> planIds(bestIds.begin(), bestIds.end());
            for (const RequiredMeal& rm : user.required_meals)
                if (planIds.count(rm.id)) honored++;
            cout << "Required meals honored: " << honored << " / " << user.required_meals.size() << endl;
        }

//        for (int id : bestIds) {
//            Meal meal = mealsById[id];
//            cout << "  " << id << " " << meal.name
//                 << " (price=" << meal.price << ", score=" << meal.score << ", value=" << meal.value << ")\n";
//        }

        writePlanToJson(bestIds, outputPath);
        cout << "Wrote output.json" << endl;
    }

    return 0;

}


/*
    The total inputs i have to have:
    1. convert the dish_dataset to Meal objects (DONE)
    2. convert the ingredient_dataset to Ingredient objects (DONE)
    3. user allergies (DONE)
    4. user disliked ingredients (DONE)
    5. user disliked meals (DONE)
    6. user liked meals (DONE)
    7. list of ingredients he have for this week (DONE)
    8. list of ingredients he have always (DONE)
    9. time he have to cook (DONE)
    10. current season (DONE)
    11. number of meals to suggest - max 7 (DONE)

    The things i have to do before beginning in dp:
    1. filter the meals and remove allergies and disliked ingredients and meals (DONE)
    2. calculate the price of each meal (DONE)
    3. calculate nutrition elements for each meal (DONE)
    4. calculate the score of each meal (DONE)
    5. pick top 100 meals depending on score with taking maximum 3 meal combinations from each unique meal (DONE)

    The things i have to have to begin the dp:
    1. array of meals each one have a score, nutrition elements, price
    2. total budget of the user
    3. number of servings
*/
