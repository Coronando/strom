
#A simple python script that calculates when its profitable to use electricity using newtons method

def my_newton(f, df, x0, tol):
    if abs(f(x0)) < tol:
        return x0
    else:
        return my_newton(f, df, x0 - f(x0)/df(x0), tol)
    

# Check if this is main
if __name__ == "__main__":
    # Price to pay = 1.25(((spot+nettwork_fee)-(avg_price-0.7)*0.9)*kwt) + fixed_network_fee
    kwt = 3000 #kWh             Forbruk over en måned (vilkårlig eksempel)
    avg_price = 1.5 #Kr/kWh       Gjennomsnittlig pris i en gitt sone
    network_fee = 0.4 #Kr/kWh   Nettleie i en gitt sone på nattetid
    fixed_network_fee = 450 #Kr Nettleie ut fra maks belastning

    f = lambda x : 1.25*((x+network_fee)-(avg_price-0.7)*0.9)*kwt + fixed_network_fee
    df = lambda x : 3750

    print("Det lønner seg å bruke strøm når prisen går under: ")
    print(my_newton(f, df, 1, 0.0001))