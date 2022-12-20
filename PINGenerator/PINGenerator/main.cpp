//
//  main.cpp
//  PINGenerator
//
//  Created by Ronald Oakes on 1/4/18.
//  Comments and other information modified on 12/20/2022
//  Copyright © 2018,2022 Ronald Oakes. All rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
//  For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
//  All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
//

/*
 This program generates 20,000 unique random PINs to be used with Hugo Award Voting
 database and front end.
 
 As written, this version of the code was written and built using XCode, and run
 under MacOS.  It has not been tested under other operating systems.
 */

#include <iostream>
#include <fstream>
#include <cstdlib>
#include <random>
#include <chrono>
#include <unordered_set>

// The number of numberical digits in the PIN
#define PIN_SIZE 16

int main(int argc, const char * argv[])
{
    // Number of unique PINs to generate
    int pins2generate = 20000;
    
    std::unordered_set<long> generatedPins;
    
    // construct a trivial random generator engine from a time-based seed:
    unsigned int seed = (unsigned int)std::chrono::system_clock::now().time_since_epoch().count();
    std::default_random_engine generator (seed);
    
    std::uniform_int_distribution<long> randomizer(1e10,1e11-1);
    
    for(int i = 0; i < pins2generate; i++)
    {
        bool done = false;
        while (!done)
        {
            long pinCandidate = randomizer(generator);
        
            if((pinCandidate > 0) && (generatedPins.count(pinCandidate) == 0))
            {
                generatedPins.emplace(pinCandidate);
                done = true;
            }
        }
    }
    
    char pinText[PIN_SIZE];
    // Replace the file name below with the file where the PINs import file will be
    // created.
    std::fstream pinFile("/Users/ron/hugo_web/PINGenerator/pin_list.csv",std::fstream::out);
    pinFile << "\"pin_entry_id\",\"pin\",\"pin_assigned\"\n";
    
    int pin_entry_id = 1;
    
    for(std::unordered_set<long>::iterator it = generatedPins.begin(); it != generatedPins.end(); ++it)
    {
        // Replace the "SJ" with a unique character Prefix to indicate the
        // particular Worldcon these PINs are for.
        snprintf(pinText,PIN_SIZE,"SJ%010ld",*it);
        
        std::cout << pinText << "\n";
        pinFile  << pin_entry_id++ << "," << pinText << ",0\n";
    }
    
    pinFile.flush();
    pinFile.close();
}
